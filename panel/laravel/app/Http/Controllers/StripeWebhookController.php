<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Webhook;
use Throwable;

/**
 * Webhook Stripe: fonte da verdade do estado de assinatura.
 * Deliveries sao reclamados atomicamente em stripe_event_deliveries.
 */
final class StripeWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $secret = (string) config('services.stripe.webhook_secret');
        if ($secret === '') {
            Log::error('billing.webhook.missing_secret');

            return response()->json(['error' => 'webhook not configured'], 500);
        }

        $signature = (string) $request->header('Stripe-Signature', '');
        $payload = $request->getContent();

        try {
            $event = Webhook::constructEvent($payload, $signature, $secret);
        } catch (Throwable $e) {
            Log::warning('billing.webhook.invalid_signature', [
                'exception' => $e::class,
            ]);

            return response()->json(['error' => 'invalid signature'], 400);
        }

        $eventId = (string) ($event->id ?? '');
        $eventType = (string) ($event->type ?? '');
        $eventCreatedAt = isset($event->created) ? (int) $event->created : null;

        if ($eventId === '') {
            Log::warning('billing.webhook.missing_event_id', ['type' => $eventType]);

            return response()->json(['error' => 'invalid event'], 400);
        }

        if (! $this->claimDelivery($eventId, $eventType, $eventCreatedAt)) {
            return response()->json(['status' => 'duplicate', 'id' => $eventId]);
        }

        try {
            DB::transaction(function () use ($event, $eventType, $eventId, $eventCreatedAt): void {
                $object = $event->data->object ?? null;
                if ($object === null) {
                    return;
                }

                match ($eventType) {
                    'checkout.session.completed' => $this->onCheckoutCompleted($object, $eventId, $eventCreatedAt),
                    'customer.subscription.updated' => $this->onSubscriptionUpdated($object, $eventId, $eventCreatedAt),
                    'customer.subscription.deleted' => $this->onSubscriptionDeleted($object, $eventId, $eventCreatedAt),
                    'invoice.payment_failed' => $this->onPaymentFailed($object, $eventId),
                    default => null,
                };
            });

            DB::table('stripe_event_deliveries')
                ->where('event_id', $eventId)
                ->update([
                    'status' => 'processed',
                    'processed_at' => now(),
                    'last_error' => null,
                    'updated_at' => now(),
                ]);
        } catch (Throwable $e) {
            DB::table('stripe_event_deliveries')
                ->where('event_id', $eventId)
                ->update([
                    'status' => 'failed',
                    'last_error' => mb_substr($e->getMessage(), 0, 1000),
                    'updated_at' => now(),
                ]);

            report($e);
            Log::error('billing.webhook.exception', [
                'type' => $eventType,
                'id' => $eventId,
                'exception' => $e::class,
            ]);

            return response()->json(['error' => 'processing failed'], 500);
        }

        return response()->json(['status' => 'ok', 'type' => $eventType, 'id' => $eventId]);
    }

    private function claimDelivery(string $eventId, string $eventType, ?int $createdAt): bool
    {
        $now = now();
        $inserted = DB::table('stripe_event_deliveries')->insertOrIgnore([
            'event_id' => $eventId,
            'event_type' => $eventType !== '' ? $eventType : null,
            'provider_created_at' => $createdAt,
            'status' => 'processing',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        if ($inserted === 1) {
            return true;
        }

        return DB::table('stripe_event_deliveries')
            ->where('event_id', $eventId)
            ->where(function ($query): void {
                $query->whereIn('status', ['received', 'failed'])
                    ->orWhere(function ($stale): void {
                        $stale->where('status', 'processing')
                            ->where('updated_at', '<', now()->subMinutes(10));
                    });
            })
            ->update([
                'status' => 'processing',
                'last_error' => null,
                'updated_at' => $now,
            ]) === 1;
    }

    private function onCheckoutCompleted(object $session, string $eventId, ?int $eventCreatedAt): void
    {
        $userId = (int) ($session->metadata->user_id ?? 0);
        $customerId = (string) ($session->customer ?? '');
        $subscriptionId = (string) ($session->subscription ?? '');

        $user = $userId > 0
            ? User::find($userId)
            : User::query()->where('stripe_customer_id', $customerId)->first();

        if ($user === null) {
            Log::warning('billing.webhook.user_not_found', ['session_id' => $session->id ?? null]);

            return;
        }

        if ($customerId !== '' && $user->stripe_customer_id !== $customerId) {
            $user->forceFill(['stripe_customer_id' => $customerId])->save();
        }

        $requestedPlanId = isset($session->metadata->plan_id) ? (int) $session->metadata->plan_id : null;
        $this->syncSubscription($user, 'active', $subscriptionId, $eventId, $eventCreatedAt, null, $requestedPlanId ?: null);
    }

    private function onSubscriptionUpdated(object $subscription, string $eventId, ?int $eventCreatedAt): void
    {
        $customerId = (string) ($subscription->customer ?? '');
        $user = User::query()->where('stripe_customer_id', $customerId)->first();
        if ($user === null) {
            return;
        }

        $status = (string) ($subscription->status ?? '');
        $subscriptionId = (string) ($subscription->id ?? '');
        $this->syncSubscription($user, $status !== '' ? $status : 'unknown', $subscriptionId, $eventId, $eventCreatedAt, $subscription);
    }

    private function onSubscriptionDeleted(object $subscription, string $eventId, ?int $eventCreatedAt): void
    {
        $customerId = (string) ($subscription->customer ?? '');
        $user = User::query()->where('stripe_customer_id', $customerId)->first();
        if ($user === null) {
            return;
        }

        $this->syncSubscription(
            $user,
            'canceled',
            (string) ($subscription->id ?? ''),
            $eventId,
            $eventCreatedAt,
            $subscription
        );
    }

    private function onPaymentFailed(object $invoice, string $eventId): void
    {
        Log::warning('billing.webhook.payment_failed', [
            'customer' => (string) ($invoice->customer ?? ''),
            'event_id' => $eventId,
        ]);
    }

    private function syncSubscription(
        User $user,
        string $status,
        string $subscriptionId,
        string $eventId,
        ?int $eventCreatedAt,
        ?object $stripeSubscription = null,
        ?int $requestedPlanId = null
    ): void {
        $current = Subscription::query()
            ->where('user_id', $user->id)
            ->where('provider', 'stripe')
            ->first();

        if (
            $eventCreatedAt !== null
            && $current?->stripe_event_created_at !== null
            && $eventCreatedAt < (int) $current->stripe_event_created_at
        ) {
            Log::notice('billing.webhook.out_of_order_ignored', [
                'user_id' => $user->id,
                'event_id' => $eventId,
            ]);

            return;
        }

        $premium = Plan::query()->where('code', 'premium')->first();
        $freePlanId = Plan::query()->where('code', 'free')->value('id');
        if ($premium === null || $freePlanId === null) {
            Log::error('billing.webhook.base_plans_missing');

            return;
        }

        $isPaidState = in_array($status, ['active', 'trialing'], true);
        $isTerminalState = in_array($status, ['canceled', 'unpaid', 'incomplete_expired'], true);
        $plan = $isTerminalState
            ? $freePlanId
            : $this->resolvePaidPlan($stripeSubscription, $requestedPlanId, $premium);

        if ($plan === null) {
            return;
        }

        $planId = $plan;

        Subscription::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'provider' => 'stripe',
            ],
            [
                'plan_id' => $planId ?: $premium->id,
                'status' => $isPaidState ? $status : $status,
                'provider_customer_id' => $user->stripe_customer_id,
                'provider_subscription_id' => $subscriptionId !== '' ? $subscriptionId : null,
                'stripe_subscription_id' => $subscriptionId !== '' ? $subscriptionId : null,
                'stripe_event_id' => $eventId,
                'stripe_event_created_at' => $eventCreatedAt,
                'current_period_start' => $this->periodDate($stripeSubscription?->current_period_start ?? null),
                'current_period_end' => $this->periodDate($stripeSubscription?->current_period_end ?? null),
                'cancel_at_period_end' => (bool) ($stripeSubscription?->cancel_at_period_end ?? false),
            ]
        );
    }

    private function resolvePaidPlan(?object $stripeSubscription, ?int $requestedPlanId, Plan $legacyPremium): ?int
    {
        $priceId = $this->stripePriceId($stripeSubscription);
        if ($priceId !== null) {
            $planId = Plan::query()
                ->where('stripe_price_id', $priceId)
                ->where('is_free', false)
                ->where('is_active', true)
                ->value('id');
            if ($planId !== null) {
                return (int) $planId;
            }

            Log::warning('billing.webhook.unknown_price', [
                'price_id' => $priceId,
            ]);

            return null;
        }

        if ($requestedPlanId !== null) {
            $plan = Plan::query()
                ->whereKey($requestedPlanId)
                ->where('is_free', false)
                ->where('is_active', true)
                ->first();

            if ($plan !== null) {
                return (int) $plan->id;
            }
        }

        // Compatibilidade apenas para assinaturas legadas sem itens expandidos.
        return $legacyPremium->id;
    }

    private function stripePriceId(?object $stripeSubscription): ?string
    {
        $items = $stripeSubscription?->items?->data ?? [];
        $first = null;
        if (is_array($items)) {
            $first = $items[0] ?? null;
        } elseif ($items instanceof \Traversable) {
            $first = $items->current();
        }

        $priceId = is_object($first) ? ($first->price->id ?? null) : null;

        return is_string($priceId) && preg_match('/^price_[A-Za-z0-9_]+$/', $priceId) === 1
            ? $priceId
            : null;
    }

    private function periodDate(mixed $timestamp): ?string
    {
        if (! is_numeric($timestamp) || (int) $timestamp <= 0) {
            return null;
        }

        return gmdate('Y-m-d H:i:s', (int) $timestamp);
    }
}
