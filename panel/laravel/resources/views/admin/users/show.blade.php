@extends('layouts.app')

@section('title', 'MElink | Detalhe do usuário')

@section('content')
    <div class="hero">
        <section class="hero-card">
            <span class="eyebrow">Conta gerenciada</span>
            <h1 class="page-title">{{ $user->name }}</h1>
            <p class="page-subtitle">
                Detalhes operacionais da conta, com visão de links, domínios e assinatura.
            </p>

            <div class="hero-actions">
                <a class="button secondary" href="{{ route('admin.users.index') }}">Voltar para lista</a>
                <a class="button secondary" href="mailto:{{ $user->email }}">Enviar e-mail</a>
            </div>
        </section>

        <aside class="hero-side">
            <div class="card compact">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Perfil</h2>
                        <p class="meta">Acesso e capacidade da conta.</p>
                    </div>
                    <span class="badge {{ $user->isOwner() ? 'info' : ($user->isPremium() ? 'success' : 'warning') }}">
                        {{ $user->role }}
                    </span>
                </div>

                <ul class="list">
                    <li><span class="label">E-mail</span><span class="value">{{ $user->email }}</span></li>
                    <li><span class="label">Plano lógico</span><span class="value">{{ $user->isOwner() ? 'Owner' : ($user->isPremium() ? 'Premium' : 'Free') }}</span></li>
                    <li><span class="label">Links</span><span class="value">{{ $user->short_links_count }}</span></li>
                    <li><span class="label">Domínios</span><span class="value">{{ $user->customer_domains_count }}</span></li>
                    <li><span class="label">Assinaturas</span><span class="value">{{ $user->subscriptions_count }}</span></li>
                </ul>
            </div>
        </aside>
    </div>

    <section class="card section">
        <div class="section-head">
            <div>
                <h2>Ações administrativas</h2>
                <p>Operações seguras para suporte e manutenção da conta.</p>
            </div>
        </div>

        <div class="actions">
            @if (! $user->isOwner())
                <form method="POST" action="{{ route('admin.users.reset-password', $user) }}">
                    @csrf
                    <button type="submit" class="primary">Gerar senha temporária</button>
                </form>
            @else
                <span class="badge info">Conta do dono protegida</span>
            @endif
        </div>
    </section>

    <section class="grid cards-2 section">
        <div class="card">
            <div class="section-head">
                <div>
                    <h2>Assinaturas</h2>
                    <p>Últimos registros ligados a essa conta.</p>
                </div>
            </div>

            @if ($subscriptions->isEmpty())
                <div class="empty">Nenhuma assinatura encontrada.</div>
            @else
                <ul class="list">
                    @foreach ($subscriptions as $subscription)
                        <li>
                            <span class="label">{{ $subscription->plan?->name ?? 'Plano' }}</span>
                            <span class="value">{{ $subscription->status }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="card">
            <div class="section-head">
                <div>
                    <h2>Atividade recente</h2>
                    <p>Links e domínios criados pela conta.</p>
                </div>
            </div>

            <div class="stack" style="gap: 16px;">
                <div>
                    <h3 style="margin: 0 0 8px;">Últimos links</h3>
                    @if ($links->isEmpty())
                        <div class="empty">Sem links registrados.</div>
                    @else
                        <ul class="list">
                            @foreach ($links as $link)
                                <li>
                                    <span class="label">{{ $link->custom_slug ?? $link->generated_slug ?? 'link' }}</span>
                                    <span class="value">{{ $link->long_url }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <div>
                    <h3 style="margin: 0 0 8px;">Últimos domínios</h3>
                    @if ($domains->isEmpty())
                        <div class="empty">Sem domínios registrados.</div>
                    @else
                        <ul class="list">
                            @foreach ($domains as $domain)
                                <li>
                                    <span class="label">{{ $domain->domain }}</span>
                                    <span class="value">{{ $domain->status }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection
