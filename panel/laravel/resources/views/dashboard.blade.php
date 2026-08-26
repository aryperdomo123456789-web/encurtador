@extends('layouts.app')

@section('title', 'MElink | Dashboard')

@section('content')
    @if ($user)
        <div class="hero">
            <section class="hero-card">
                <span class="eyebrow">Visão geral operacional</span>
                <h1 class="page-title">Olá, {{ $user->name }}</h1>
                <p class="page-subtitle">
                    Este é o centro de controle do SaaS MElink. A partir daqui você acompanha links, domínios,
                    plano ativo e a saúde da operação sem encostar no motor de redirecionamento.
                </p>

                <div class="hero-actions">
                    <a class="button primary" href="{{ route('links.create') }}">Criar link gratuito</a>
                    <a class="button secondary" href="{{ route('links.index') }}">Ver links</a>
                    @if ($isPremium)
                        <a class="button secondary" href="{{ route('links.premium') }}">Novo link premium</a>
                        <a class="button secondary" href="{{ route('domains.index') }}">Domínios</a>
                    @endif
                </div>
            </section>

            <aside class="hero-side">
                <div class="stat-strip">
                    <div class="stat-card">
                        <strong>{{ $currentPlan }}</strong>
                        <span>Plano ativo</span>
                    </div>
                    <div class="stat-card">
                        <strong>{{ $isPremium ? 'Premium' : 'Free' }}</strong>
                        <span>Status da conta</span>
                    </div>
                </div>

                <div class="card compact">
                    <div class="card-header">
                        <div>
                            <h2 class="card-title">Cota deste mês</h2>
                            <p class="meta">Janela UTC para evitar divergência de virada de mês.</p>
                        </div>
                        <span class="badge {{ $remainingFreeLinks > 0 ? 'info' : 'warning' }}">
                            {{ $remainingFreeLinks > 0 ? 'Disponível' : 'Esgotada' }}
                        </span>
                    </div>

                    <div class="grid" style="gap: 10px;">
                        <div class="kpi">
                            <strong>{{ $linksThisMonth }}</strong>
                            <span>links criados no mês</span>
                        </div>
                        <div class="kpi">
                            <strong>{{ $remainingFreeLinks }}</strong>
                            <span>restantes da cota free</span>
                        </div>
                        <div class="kpi">
                            <strong>{{ $freeLimit }}</strong>
                            <span>limite mensal padrão</span>
                        </div>
                    </div>
                </div>
            </aside>
        </div>

        <div class="grid cards-4 section">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Links totais</h2>
                    <span class="badge info">Histórico</span>
                </div>
                <div class="kpi"><strong>{{ $totalLinks }}</strong><span>cadastros no painel</span></div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Domínios</h2>
                    <span class="badge success">Ativos</span>
                </div>
                <div class="kpi"><strong>{{ $totalDomains }}</strong><span>registrados</span></div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Domínios ativos</h2>
                    <span class="badge success">OK</span>
                </div>
                <div class="kpi"><strong>{{ $activeDomains }}</strong><span>já verificados</span></div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Pendentes</h2>
                    <span class="badge warning">Atenção</span>
                </div>
                <div class="kpi"><strong>{{ $pendingDomains }}</strong><span>aguardando DNS</span></div>
            </div>
        </div>

        <div class="grid cards-2 section">
            <section class="table-panel">
                <div class="section-head">
                    <div>
                        <h2>Links recentes</h2>
                        <p>Últimos registros criados pelo seu usuário.</p>
                    </div>
                    <a class="button ghost" href="{{ route('links.index') }}">Abrir lista</a>
                </div>

                @if ($recentLinks->isEmpty())
                    <div class="empty">Nenhum link criado ainda. Comece com um link gratuito para validar o fluxo.</div>
                @else
                    <table class="table">
                        <thead>
                        <tr>
                            <th>Destino</th>
                            <th>Status</th>
                            <th>Link</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($recentLinks as $link)
                            <tr>
                                <td>
                                    <div style="font-weight:700; word-break: break-word;">{{ $link->long_url }}</div>
                                    <div class="meta">
                                        {{ $link->domain }} ·
                                        {{ $link->is_free_link ? 'free' : 'premium' }}
                                    </div>
                                </td>
                                <td>
                                    <span class="badge {{ $link->status === 'active' ? 'success' : ($link->status === 'failed' ? 'danger' : 'warning') }}">
                                        {{ $link->status }}
                                    </span>
                                </td>
                                <td>
                                    @if ($link->shlink_short_url)
                                        <a href="{{ $link->shlink_short_url }}" target="_blank" rel="noopener">{{ $link->shlink_short_url }}</a>
                                    @else
                                        <span class="muted">Aguardando emissão</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
            </section>

            <section class="table-panel">
                <div class="section-head">
                    <div>
                        <h2>Domínios recentes</h2>
                        <p>Resumo da verificação DNS e do estado TLS.</p>
                    </div>
                    @if ($isPremium)
                        <a class="button ghost" href="{{ route('domains.index') }}">Gerenciar</a>
                    @endif
                </div>

                @if ($recentDomains->isEmpty())
                    <div class="empty">Nenhum domínio próprio registrado ainda.</div>
                @else
                    <table class="table">
                        <thead>
                        <tr>
                            <th>Domínio</th>
                            <th>DNS</th>
                            <th>TLS</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($recentDomains as $domain)
                            <tr>
                                <td>
                                    <div style="font-weight:700;">{{ $domain->domain }}</div>
                                    <div class="meta">{{ $domain->status }}</div>
                                </td>
                                <td>
                                    <span class="badge {{ $domain->status === 'active' ? 'success' : 'warning' }}">
                                        {{ $domain->dns_verified_at ? 'verificado' : 'pendente' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge {{ $domain->tls_status === 'active' ? 'success' : ($domain->tls_status === 'error' ? 'danger' : 'warning') }}">
                                        {{ $domain->tls_status }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
            </section>
        </div>

        <div class="grid cards-2 section">
            <section class="card">
                <div class="section-head">
                    <div>
                        <h2>Plano e renovação</h2>
                        <p>Resumo da assinatura vinculada à conta.</p>
                    </div>
                </div>

                <ul class="list">
                    <li>
                        <span class="label">Plano atual</span>
                        <span class="value">{{ $currentPlan }}</span>
                    </li>
                    <li>
                        <span class="label">Última atualização</span>
                        <span class="value">{{ $subscription?->updated_at?->format('d/m/Y H:i') ?? 'n/d' }}</span>
                    </li>
                    <li>
                        <span class="label">Próximo reset da cota</span>
                        <span class="value">{{ $nextResetAt->format('d/m/Y H:i') }} UTC</span>
                    </li>
                    <li>
                        <span class="label">Uso mensal registrado</span>
                        <span class="value">{{ $monthlyUsage?->free_links_created ?? 0 }}</span>
                    </li>
                </ul>
            </section>

            <section class="card">
                <div class="section-head">
                    <div>
                        <h2>Atalho operacional</h2>
                        <p>Fluxos mais usados para continuar a configuração.</p>
                    </div>
                </div>

                <div class="stack">
                    <a class="button primary" href="{{ route('links.create') }}">Abrir criador de links</a>
                    <a class="button secondary" href="{{ route('billing.index') }}">Ver assinatura</a>
                    @if ($isPremium)
                        <a class="button secondary" href="{{ route('domains.index') }}">Cadastrar domínio</a>
                    @endif
                </div>
            </section>
        </div>
    @else
        <div class="hero">
            <section class="hero-card">
                <span class="eyebrow">Acesso público</span>
                <h1 class="page-title">Painel MElink</h1>
                <p class="page-subtitle">
                    O painel administrativo fica disponível para a equipe em
                    <code>{{ config('panel.host', 'me.vr766.com') }}</code>.
                    Entre para acessar links, domínios, cobrança e métricas.
                </p>

                <div class="hero-actions">
                    <a class="button primary" href="{{ route('login') }}">Entrar no painel</a>
                </div>
            </section>

            <aside class="hero-side">
                <div class="card compact">
                    <div class="card-header">
                        <div>
                            <h2 class="card-title">Separação de rotas</h2>
                            <p class="meta">O host público de slugs continua isolado do painel.</p>
                        </div>
                        <span class="badge info">OK</span>
                    </div>
                    <ul class="list">
                        <li>
                            <span class="label">Painel</span>
                            <span class="value">/login, /links, /domains</span>
                        </li>
                        <li>
                            <span class="label">Motor</span>
                            <span class="value">/{slug} e redirect</span>
                        </li>
                        <li>
                            <span class="label">Saúde</span>
                            <span class="value">/healthz e /health/ready</span>
                        </li>
                    </ul>
                </div>
            </aside>
        </div>
    @endif
@endsection
