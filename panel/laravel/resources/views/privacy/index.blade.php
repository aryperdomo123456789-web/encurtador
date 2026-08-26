@extends('layouts.app')

@section('title', 'MElink | Privacidade e dados')

@section('content')
    <div class="hero">
        <section class="hero-card">
            <span class="eyebrow">Controle dos seus dados</span>
            <h1 class="page-title">Privacidade sem conversa fiada</h1>
            <p class="page-subtitle">Baixe uma cópia estruturada dos dados da sua conta, links, domínios, assinatura, workspaces, tokens e eventos de conversão associados ao seu usuário.</p>
        </section>
        <aside class="hero-side">
            <div class="card compact">
                <h2 class="card-title">Exportação LGPD</h2>
                <p class="meta">JSON · escopo da sua conta · sem segredos</p>
                <a class="button" href="{{ route('privacy.export') }}">Baixar meus dados</a>
            </div>
        </aside>
    </div>

    <section class="grid-two">
        <div class="card">
            <h2 class="card-title">O que entra no arquivo</h2>
            <p class="meta">Dados cadastrais, links criados por você, domínios próprios, status de assinatura, participação em workspaces, uso de quota, tokens por prefixo e eventos de conversão atribuídos à sua conta.</p>
        </div>
        <div class="card">
            <h2 class="card-title">O que nunca sai</h2>
            <p class="meta">Senhas, tokens completos, hashes de IP/User-Agent, chaves de API, segredos Stripe e arquivos de configuração ficam fora do export. Segurança não é enfeite de landing page, campeão.</p>
        </div>
    </section>
@endsection
