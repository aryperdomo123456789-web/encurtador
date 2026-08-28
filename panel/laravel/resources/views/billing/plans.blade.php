@extends('layouts.app')

@section('title', 'MElink | Assinatura')

@section('content')
    <div class="hero">
        <section class="hero-card">
            <span class="eyebrow">Billing e plano</span>
            <h1 class="page-title">Assinatura</h1>
            <p class="page-subtitle">
                A cobrança fica isolada do fluxo de redirecionamento. Use esta área para comparar planos,
                acompanhar o status Stripe e abrir o portal de gestão quando necessário.
            </p>

            <div class="hero-actions">
                @if ($isOwner)
                    <span class="badge info">Conta do dono</span>
                @elseif ($isPremium)
                    <span class="badge success">Plano ativo</span>
                    <form method="POST" action="{{ route('billing.portal') }}" style="display:inline;">
                        @csrf
                        <button type="submit" class="button secondary">Abrir portal</button>
                    </form>
                @else
                    <span class="badge warning">Free</span>
                    <a class="button secondary" href="#planos">Ver planos</a>
                @endif
            </div>
        </section>

        <aside class="hero-side">
            <div class="card compact">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Status atual</h2>
                        <p class="meta">Resumo da última assinatura disponível.</p>
                    </div>
                    <span class="badge {{ $isOwner ? 'info' : ($isPremium ? 'success' : 'warning') }}">
                        {{ $isOwner ? 'owner' : ($isPremium ? 'premium' : 'free') }}
                    </span>
                </div>

                <ul class="list">
                    <li>
                        <span class="label">Plano atual</span>
                        <span class="value">{{ $subscription?->plan?->name ?? 'Free' }}</span>
                    </li>
                    <li>
                        <span class="label">Assinatura</span>
                        <span class="value">{{ $subscription?->stripe_subscription_id ?? $subscription?->provider_subscription_id ?? 'n/d' }}</span>
                    </li>
                    <li>
                        <span class="label">Status Stripe</span>
                        <span class="value">{{ $subscription?->status ?? 'n/d' }}</span>
                    </li>
                </ul>
            </div>
        </aside>
    </div>

    @if(!empty($flash))
        <div class="alert info" style="border-color: rgba(36, 71, 245, 0.18); background: rgba(36, 71, 245, 0.08); color: #1d4ed8;">
            {{ $flash }}
        </div>
    @endif

    <section class="grid cards-2 section" id="planos">
        @foreach($plans as $plan)
            <div class="card">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">{{ $plan->name }}</h2>
                        <p class="meta">{{ $plan->description }}</p>
                    </div>
                    <span class="badge {{ $plan->is_free ? 'muted' : 'success' }}">
                        {{ $plan->is_free ? 'free' : 'premium' }}
                    </span>
                </div>

                <ul class="list">
                    <li>
                        <span class="label">Preço mensal</span>
                        <span class="value">{{ $plan->is_free ? 'R$ 0,00' : 'R$ '.number_format($plan->monthly_price_cents / 100, 2, ',', '.') }}</span>
                    </li>
                    <li>
                        <span class="label">Limite mensal</span>
                        <span class="value">{{ $plan->monthly_short_url_limit === null ? 'ilimitado' : number_format($plan->monthly_short_url_limit, 0, ',', '.') . ' links' }}</span>
                    </li>
                    <li>
                        <span class="label">Pool de cliques</span>
                        <span class="value">{{ $plan->monthly_click_limit === null ? 'ilimitado' : number_format($plan->monthly_click_limit, 0, ',', '.') . ' cliques' }}</span>
                    </li>
                    <li>
                        <span class="label">Domínios próprios</span>
                        <span class="value">{{ $plan->custom_domain_limit > 0 ? $plan->custom_domain_limit : 'nenhum' }}</span>
                    </li>
                    <li>
                        <span class="label">Slug customizado</span>
                        <span class="value">{{ $plan->allow_custom_slug ? 'sim' : 'não' }}</span>
                    </li>
                    <li>
                        <span class="label">Domínio próprio</span>
                        <span class="value">{{ $plan->allow_custom_domain ? 'sim' : 'não' }}</span>
                    </li>
                    <li>
                        <span class="label">Expiração custom</span>
                        <span class="value">{{ $plan->allow_custom_expiration ? 'sim' : 'não' }}</span>
                    </li>
                    <li>
                        <span class="label">Links vitalícios</span>
                        <span class="value">{{ $plan->allow_lifetime_links ? 'sim' : 'não' }}</span>
                    </li>
                </ul>

                @if(!$plan->is_free)
                    <div class="actions" style="margin-top: 18px;">
                        @if($isOwner)
                            <span class="badge info">Acesso do dono liberado</span>
                        @elseif($isPremium)
                            <form method="POST" action="{{ route('billing.portal') }}">
                                @csrf
                                <button class="button primary" type="submit">Gerenciar assinatura</button>
                            </form>
                        @else
                            @if($plan->stripe_price_id)
                                <form method="POST" action="{{ route('billing.checkout') }}">
                                    @csrf
                                    <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                                    <button class="button primary" type="submit">Assinar {{ $plan->name }}</button>
                                </form>
                            @else
                                <span class="badge warning">Checkout em preparação</span>
                            @endif
                        @endif
                    </div>
                @endif
            </div>
        @endforeach
    </section>

    @if($subscription)
        <section class="card section">
            <div class="section-head">
                <div>
                    <h2>Detalhes Stripe</h2>
                    <p>Informação útil para suporte e auditoria.</p>
                </div>
            </div>
            <ul class="list">
                <li>
                    <span class="label">Customer</span>
                    <span class="value">{{ $subscription->provider_customer_id ?? $subscription->user->stripe_customer_id ?? 'n/d' }}</span>
                </li>
                <li>
                    <span class="label">Subscription</span>
                    <span class="value">{{ $subscription->stripe_subscription_id ?? $subscription->provider_subscription_id ?? 'n/d' }}</span>
                </li>
                <li>
                    <span class="label">Cancelamento no fim do período</span>
                    <span class="value">{{ $subscription->cancel_at_period_end ? 'sim' : 'não' }}</span>
                </li>
            </ul>
        </section>
    @endif
@endsection
