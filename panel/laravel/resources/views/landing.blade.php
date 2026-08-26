@extends('layouts.app')

@section('title', 'MElink')
@section('meta_description', 'Painel SaaS para encurtar, organizar e operar links, domínios próprios, métricas e cobrança.')

@section('content')
    <div class="hero">
        <section class="hero-card">
            <span class="eyebrow">Plataforma de links</span>
            <h1 class="page-title">Reduza, organize e opere seus links com padrão de produto grande.</h1>
            <p class="page-subtitle">
                Um painel SaaS para controlar encurtamento, domínios próprios, analytics e cobrança em um fluxo
                limpo. A borda cuida dos redirecionamentos. O painel cuida da operação.
            </p>

            <div class="hero-actions">
                <a class="button primary" href="{{ route('register') }}">Criar conta gratuita</a>
                <a class="button secondary" href="{{ route('login') }}">Entrar no painel</a>
                <a class="button ghost" href="#como-funciona">Ver como funciona</a>
            </div>

            <div class="stack" style="grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 10px; margin-top: 18px;">
                <div class="mini-card">
                    <strong>Free</strong>
                    <span>Cota mensal e expiração automática</span>
                </div>
                <div class="mini-card">
                    <strong>Premium</strong>
                    <span>Slug customizado e domínio próprio</span>
                </div>
                <div class="mini-card">
                    <strong>Stripe</strong>
                    <span>Checkout, portal e webhook</span>
                </div>
                <div class="mini-card">
                    <strong>MElink</strong>
                    <span>Motor isolado e rápido</span>
                </div>
            </div>
        </section>

        <aside class="hero-side">
            <div class="card compact">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Operação separada por camada</h2>
                        <p class="meta">O painel não disputa rota com os slugs públicos.</p>
                    </div>
                    <span class="badge info">Arquitetura</span>
                </div>

                <ul class="list">
                    <li>
                        <span class="label">Painel</span>
                        <span class="value">/login, /register, /links</span>
                    </li>
                    <li>
                        <span class="label">Redirect</span>
                        <span class="value">/{slug} e domínios de cliente</span>
                    </li>
                    <li>
                        <span class="label">Saúde</span>
                        <span class="value">/healthz e /health/ready</span>
                    </li>
                </ul>
            </div>

            <div class="card compact">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Pronto para escalar</h2>
                        <p class="meta">Inspirado em páginas SaaS de alto padrão.</p>
                    </div>
                </div>

                <div class="stack">
                    <div class="kpi">
                        <strong>1</strong>
                        <span>fluxo claro para entrar ou criar conta</span>
                    </div>
                    <div class="kpi">
                        <strong>2</strong>
                        <span>CTAs principais com caminho objetivo</span>
                    </div>
                    <div class="kpi">
                        <strong>3</strong>
                        <span>blocos de valor: links, domínios e métricas</span>
                    </div>
                </div>
            </div>
        </aside>
    </div>

    <section class="grid cards-3 section">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Links com regra de negócio</h2>
                <span class="badge success">Core</span>
            </div>
            <p class="meta">
                Crie links gratuitos com slug aleatório e expiração de 7 dias, ou use o fluxo premium para
                controle mais fino e personalização.
            </p>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Domínios próprios</h2>
                <span class="badge info">SSL</span>
            </div>
            <p class="meta">
                Cadastre o domínio do cliente, valide DNS e acompanhe o status de TLS com observabilidade
                pronta para operação real.
            </p>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Métricas e billing</h2>
                <span class="badge warning">Growth</span>
            </div>
            <p class="meta">
                Analytics via MElink e cobrança via Stripe em uma interface desenhada para time comercial e
                operação técnica falarem a mesma língua.
            </p>
        </div>
    </section>

    <section class="grid cards-2 section" id="como-funciona">
        <div class="card">
            <div class="section-head">
                <div>
                    <h2>Como funciona</h2>
                    <p>Um fluxo direto, previsível e fácil de explicar para o usuário final.</p>
                </div>
            </div>

            <ul class="list">
                <li>
                    <span class="label">01. Criar conta</span>
                    <span class="value">Acesso imediato ao painel</span>
                </li>
                <li>
                    <span class="label">02. Publicar links</span>
                    <span class="value">Fluxo free ou premium</span>
                </li>
                <li>
                    <span class="label">03. Evoluir operação</span>
                    <span class="value">Domínios, métricas e assinatura</span>
                </li>
            </ul>
        </div>

        <div class="card">
            <div class="section-head">
                <div>
                    <h2>Por que fica convincente</h2>
                    <p>Os padrões visuais seguem o que funciona em produtos SaaS líderes.</p>
                </div>
            </div>

            <div class="stack">
                <div class="mini-card">
                    <strong>Hero forte</strong>
                    <span>Título claro, subtexto curto e dois CTAs principais.</span>
                </div>
                <div class="mini-card">
                    <strong>Prova rápida</strong>
                    <span>Blocos objetivos que explicam valor sem poluir a tela.</span>
                </div>
                <div class="mini-card">
                    <strong>Escopo honesto</strong>
                    <span>O painel e o redirect não se confundem.</span>
                </div>
            </div>
        </div>
    </section>

    <section class="card section">
        <div class="section-head">
            <div>
                <h2>Pronto para começar</h2>
                <p>Escolha o caminho que faz sentido agora e ajuste o resto depois.</p>
            </div>
        </div>

        <div class="hero-actions">
            <a class="button primary" href="{{ route('register') }}">Criar conta</a>
            <a class="button secondary" href="{{ route('login') }}">Entrar</a>
        </div>
    </section>
@endsection
