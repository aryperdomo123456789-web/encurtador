<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Stripe\BillingPortal\Session as PortalSession;
use Stripe\StripeClient;
use Throwable;

/**
 * Controla assinaturas Stripe: checkout, portal, sucesso e cancelamento.
 * O webhook fica em StripeWebhookController (fonte da verdade do plano).
 */
final class BillingController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $plans = Plan::query()
            ->where('is_active', true)
            ->where('is_public', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
        $subscription = Subscription::query()
            ->where('user_id', $user->id)
            ->where('provider', 'stripe')
            ->latest('id')
            ->first();

        return view('billing.plans', [
            'plans' => $plans,
            'subscription' => $subscription,
            'isPremium' => (bool) $user->isPremium(),
            'isOwner' => (bool) $user->isOwner(),
        ]);
    }

    public function checkout(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'plan_id' => ['required', 'integer'],
        ]);
        $plan = Plan::query()
            ->whereKey((int) $data['plan_id'])
            ->where('is_active', true)
            ->where('is_public', true)
            ->where('is_free', false)
            ->first();

        if ($plan === null) {
            return back()->withErrors(['billing' => 'Plano indisponível para assinatura.']);
        }

        $secret = (string) config('services.stripe.secret');
        $priceId = (string) $plan->stripe_price_id;

        if ($secret === '' || $priceId === '') {
            return back()->withErrors(['billing' => 'Este plano ainda não está conectado a um Price Stripe.']);
        }

        $user = $request->user();
        if ($user->isOwner()) {
            return redirect()
                ->route('billing.index')
                ->with('status', 'Conta do dono usa acesso interno e nao depende de Stripe.');
        }

        if ($user->isPremium()) {
            return redirect()
                ->route('billing.index')
                ->with('status', 'Sua conta já possui uma assinatura paga ativa. Use o portal para gerenciá-la.');
        }

        $idempotencyKey = (string) $request->header('Idempotency-Key', '');
        if (! preg_match('/^[A-Za-z0-9._:-]{1,80}$/', $idempotencyKey)) {
            $idempotencyKey = (string) Str::uuid();
        }

        $stripe = new StripeClient($secret);

        try {
            if ($user->stripe_customer_id === null) {
                $customer = $stripe->customers->create([
                    'email' => $user->email,
                    'name' => $user->name,
                    'metadata' => ['user_id' => (string) $user->id],
                ], [
                    'idempotency_key' => 'melink-customer-'.$user->id,
                ]);
                $user->forceFill(['stripe_customer_id' => $customer->id])->save();
            }

            $session = $stripe->checkout->sessions->create([
                'mode' => 'subscription',
                'customer' => $user->stripe_customer_id,
                'line_items' => [[
                    'price' => $priceId,
                    'quantity' => 1,
                ]],
                'success_url' => route('billing.success').'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('billing.cancel'),
                'metadata' => [
                    'user_id' => (string) $user->id,
                    'plan_id' => (string) $plan->id,
                    'plan_code' => (string) $plan->code,
                ],
            ], [
                'idempotency_key' => 'melink-checkout-'.$user->id.'-'.$plan->id.'-'.$idempotencyKey,
            ]);
        } catch (Throwable $e) {
            report($e);
            Log::warning('billing.checkout_failed', [
                'request_id' => $request->attributes->get('request_id'),
                'user_id' => $request->user()->id,
                'exception' => $e::class,
            ]);

            return back()->withErrors(['billing' => 'Falha ao iniciar o checkout. Tente novamente em instantes.']);
        }

        return redirect()->away($session->url);
    }

    public function portal(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user->isOwner()) {
            return redirect()
                ->route('billing.index')
                ->with('status', 'Conta do dono usa acesso interno e nao depende de Stripe.');
        }

        if ($user->stripe_customer_id === null) {
            return redirect()->route('billing.index')->withErrors([
                'billing' => 'Nenhuma assinatura ativa encontrada.',
            ]);
        }

        $secret = (string) config('services.stripe.secret');
        if ($secret === '') {
            return redirect()->route('billing.index')->withErrors([
                'billing' => 'Portal de cobrança ainda não está configurado. Contate o suporte.',
            ]);
        }

        try {
            $portal = PortalSession::create([
                'customer' => $user->stripe_customer_id,
                'return_url' => route('billing.index'),
            ], ['api_key' => (string) config('services.stripe.secret')]);
        } catch (Throwable $e) {
            report($e);
            Log::warning('billing.portal_failed', [
                'request_id' => $request->attributes->get('request_id'),
                'user_id' => $request->user()->id,
                'exception' => $e::class,
            ]);

            return back()->withErrors(['billing' => 'Falha ao abrir o portal de cobrança. Tente novamente em instantes.']);
        }

        return redirect()->away($portal->url);
    }

    public function success(Request $request): View
    {
        // O webhook e quem atualiza plano/subscription. Aqui so damos feedback.
        return view('billing.plans', [
            'plans' => Plan::query()->orderBy('id')->get(),
            'subscription' => Subscription::query()
                ->where('user_id', $request->user()->id)
                ->where('provider', 'stripe')
                ->latest('id')->first(),
            'isPremium' => (bool) $request->user()->fresh()->isPremium(),
            'isOwner' => (bool) $request->user()->fresh()->isOwner(),
            'flash' => 'Pagamento concluido. Se seu plano ainda nao apareceu como Premium, atualize em alguns segundos (aguardando webhook).',
        ]);
    }

    public function cancel(Request $request): View
    {
        return view('billing.plans', [
            'plans' => Plan::query()->orderBy('id')->get(),
            'subscription' => Subscription::query()
                ->where('user_id', $request->user()->id)
                ->where('provider', 'stripe')
                ->latest('id')->first(),
            'isPremium' => (bool) $request->user()->isPremium(),
            'isOwner' => (bool) $request->user()->isOwner(),
            'flash' => 'Checkout cancelado. Voce continua no plano atual.',
        ]);
    }
}
