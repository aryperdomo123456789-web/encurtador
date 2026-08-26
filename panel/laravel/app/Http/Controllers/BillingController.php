<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Stripe\Checkout\Session as CheckoutSession;
use Stripe\BillingPortal\Session as PortalSession;
use Stripe\Customer;
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
        $plans = Plan::query()->orderBy('id')->get();
        $subscription = Subscription::query()
            ->where('user_id', $user->id)
            ->where('provider', 'stripe')
            ->latest('id')
            ->first();

        return view('billing.plans', [
            'plans'        => $plans,
            'subscription' => $subscription,
            'isPremium'    => (bool) $user->isPremium(),
            'isOwner'      => (bool) $user->isOwner(),
        ]);
    }

    public function checkout(Request $request): RedirectResponse
    {
        $secret = (string) config('services.stripe.secret');
        $priceId = (string) config('services.stripe.premium_price_id');

        if ($secret === '' || $priceId === '') {
            return back()->withErrors(['billing' => 'Billing nao configurado. Contate o suporte.']);
        }

        $user = $request->user();
        if ($user->isOwner()) {
            return redirect()
                ->route('billing.index')
                ->with('status', 'Conta do dono usa acesso interno e nao depende de Stripe.');
        }

        $stripe = new StripeClient($secret);

        try {
            if ($user->stripe_customer_id === null) {
                $customer = $stripe->customers->create([
                    'email'    => $user->email,
                    'name'     => $user->name,
                    'metadata' => ['user_id' => (string) $user->id],
                ]);
                $user->forceFill(['stripe_customer_id' => $customer->id])->save();
            }

            $session = $stripe->checkout->sessions->create([
                'mode'        => 'subscription',
                'customer'    => $user->stripe_customer_id,
                'line_items'  => [[
                    'price'    => $priceId,
                    'quantity' => 1,
                ]],
                'success_url' => route('billing.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url'  => route('billing.cancel'),
                'metadata'    => ['user_id' => (string) $user->id],
            ]);
        } catch (Throwable $e) {
            report($e);
            return back()->withErrors(['billing' => 'Falha ao iniciar checkout: ' . $e->getMessage()]);
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

        try {
            $portal = PortalSession::create([
                'customer'    => $user->stripe_customer_id,
                'return_url'  => route('billing.index'),
            ], ['api_key' => (string) config('services.stripe.secret')]);
        } catch (Throwable $e) {
            report($e);
            return back()->withErrors(['billing' => 'Falha ao abrir portal Stripe.']);
        }

        return redirect()->away($portal->url);
    }

    public function success(Request $request): View
    {
        // O webhook e quem atualiza plano/subscription. Aqui so damos feedback.
        return view('billing.plans', [
            'plans'        => Plan::query()->orderBy('id')->get(),
            'subscription' => Subscription::query()
                ->where('user_id', $request->user()->id)
                ->where('provider', 'stripe')
                ->latest('id')->first(),
            'isPremium'    => (bool) $request->user()->fresh()->isPremium(),
            'isOwner'      => (bool) $request->user()->fresh()->isOwner(),
            'flash'        => 'Pagamento concluido. Se seu plano ainda nao apareceu como Premium, atualize em alguns segundos (aguardando webhook).',
        ]);
    }

    public function cancel(Request $request): View
    {
        return view('billing.plans', [
            'plans'        => Plan::query()->orderBy('id')->get(),
            'subscription' => Subscription::query()
                ->where('user_id', $request->user()->id)
                ->where('provider', 'stripe')
                ->latest('id')->first(),
            'isPremium'    => (bool) $request->user()->isPremium(),
            'isOwner'      => (bool) $request->user()->isOwner(),
            'flash'        => 'Checkout cancelado. Voce continua no plano atual.',
        ]);
    }
}
