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
 * Idempotente via stripe_event_id.
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
        $payload   = $request->getContent();

        try {
            $event = Webhook::constructEvent($payload, $signature, $secret);
        } catch (Throwable $e) {
            Log::warning('billing.webhook.invalid_signature', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'invalid signature'], 400);
        }

        $eventId   = (string) ($event->id ?? '');
        $eventType = (string) ($event->type ?? '');

        // Idempotencia: mesmo event_id nao roda duas vezes.
        if ($eventId !== '' && Subscription::query()->where('stripe_event_id', $eventId)->exists()) {
            return response()->json(['status' => 'duplicate', 'id' => $eventId]);
        }

        try {
            DB::transaction(function () use ($event, $eventType, $eventId): void {
                $object = $event->data->object ?? null;
                if ($object === null) {
                    return;
                }

                match ($eventType) {
                    'checkout.session.completed'     => $this->onCheckoutCompleted($object, $eventId),
                    'customer.subscription.updated'  => $this->onSubscriptionUpdated($object, $eventId),
                    'customer.subscription.deleted'  => $this->onSubscriptionDeleted($object, $eventId),
                    'invoice.payment_failed'         => $this->onPaymentFailed($object, $eventId),
                    default                          => null,
                };
            });
        } catch (Throwable $e) {
            report($e);
            Log::error('billing.webhook.exception', [
                'type'  => $eventType,
                'id'    => $eventId,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'processing failed'], 500);
        }

        return response()->json(['status' => 'ok', 'type' => $eventType, 'id' => $eventId]);
    }

    private function onCheckoutCompleted(object $session, string $eventId): void
    {
        $userId = (int) ($session->metadata->user_id ?? 0);
        $customerId = (string) ($session->customer ?? '');
        $subscriptionId = (string) ($session->subscription ?? '');

        $user = $userId > 0 ? User::find($userId) : User::query()->where('stripe_customer_id', $customerId)->first();
        if ($user === null) {
            Log::warning('billing.webhook.user_not_found', ['session_id' => $session->id ?? null]);
            return;
        }

        if ($customerId !== '' && $user->stripe_customer_id !== $customerId) {
            $user->forceFill(['stripe_customer_id' => $customerId])->save();
        }

        $this->activatePremium($user, $subscriptionId, $eventId);
    }

    private function onSubscriptionUpdated(object $subscription, string $eventId): void
    {
        $customerId = (string) ($subscription->customer ?? '');
        $status     = (string) ($subscription->status ?? '');
        $user = User::query()->where('stripe_customer_id', $customerId)->first();
        if ($user === null) {
            return;
        }

        if (in_array($status, ['active', 'trialing'], true)) {
            $this->activatePremium($user, (string) $subscription->id, $eventId);
            return;
        }

        if (in_array($status, ['canceled', 'unpaid', 'incomplete_expired'], true)) {
            $this->revertToFree($user, (string) $subscription->id, $eventId);
        }
    }

    private function onSubscriptionDeleted(object $subscription, string $eventId): void
    {
        $customerId = (string) ($subscription->customer ?? '');
        $user = User::query()->where('stripe_customer_id', $customerId)->first();
        if ($user === null) {
            return;
        }
        $this->revertToFree($user, (string) $subscription->id, $eventId);
    }

    private function onPaymentFailed(object $invoice, string $eventId): void
    {
        $customerId = (string) ($invoice->customer ?? '');
        Log::warning('billing.webhook.payment_failed', [
            'customer' => $customerId,
            'event_id' => $eventId,
        ]);
        // Nao rebaixa imediatamente: Stripe reprocessa. subscription.updated cuidara se falhar de vez.
    }

    private function activatePremium(User $user, string $subscriptionId, string $eventId): void
    {
        $premium = Plan::query()->where('code', 'premium')->first();
        if ($premium === null) {
            Log::error('billing.webhook.premium_plan_missing');
            return;
        }

        Subscription::updateOrCreate(
            [
                'user_id'  => $user->id,
                'provider' => 'stripe',
            ],
            [
                'plan_id'                => $premium->id,
                'status'                 => 'active',
                'provider_customer_id'   => $user->stripe_customer_id,
                'provider_subscription_id' => $subscriptionId ?: null,
                'stripe_subscription_id' => $subscriptionId ?: null,
                'stripe_event_id'        => $eventId ?: null,
                'current_period_end'     => null,
            ]
        );
    }

    private function revertToFree(User $user, string $subscriptionId, string $eventId): void
    {
        $free = Plan::query()->where('code', 'free')->first();

        Subscription::query()
            ->where('user_id', $user->id)
            ->where('provider', 'stripe')
            ->update([
                'plan_id'                  => $free?->id,
                'status'                   => 'canceled',
                'stripe_subscription_id'    => $subscriptionId ?: null,
                'stripe_event_id'          => $eventId ?: null,
            ]);
    }
}
