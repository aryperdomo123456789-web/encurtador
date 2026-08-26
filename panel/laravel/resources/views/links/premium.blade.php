@extends('layouts.app')

@section('title', 'MElink | Criar link premium')

@section('content')
    <div class="hero">
        <section class="hero-card">
            <span class="eyebrow">Fluxo premium</span>
            <h1 class="page-title">Criar link premium</h1>
            <p class="page-subtitle">
                Aqui você define o slug manualmente e, se quiser, informa também uma data de expiração.
                Essa tela só faz sentido para contas com permissão premium.
            </p>

            <div class="hero-actions">
                <a class="button secondary" href="{{ route('links.index') }}">Voltar para links</a>
                <a class="button secondary" href="{{ route('billing.index') }}">Ver assinatura</a>
            </div>
        </section>

        <aside class="hero-side">
            <div class="card compact">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Regras premium</h2>
                        <p class="meta">Slug escolhido pelo usuário e expiração opcional.</p>
                    </div>
                    <span class="badge success">Premium</span>
                </div>

                <ul class="list">
                    <li>
                        <span class="label">Slug personalizado</span>
                        <span class="value">Sim</span>
                    </li>
                    <li>
                        <span class="label">Expiração</span>
                        <span class="value">Opcional</span>
                    </li>
                    <li>
                        <span class="label">Limite free</span>
                        <span class="value">Não aplicado</span>
                    </li>
                </ul>
            </div>
        </aside>
    </div>

    <section class="card">
        <div class="section-head">
            <div>
                <h2>Detalhes do link</h2>
                <p>Escolha um slug elegante e consistente com a sua marca.</p>
            </div>
        </div>

        <form method="post" action="{{ route('links.premium.store') }}" class="form-grid">
            @csrf

            <div class="field">
                <label for="long_url">URL longa</label>
                <input
                    id="long_url"
                    type="url"
                    name="long_url"
                    value="{{ old('long_url') }}"
                    maxlength="2048"
                    placeholder="https://suaempresa.com/campanha"
                    required>
            </div>

            <div class="field">
                <label for="custom_slug">Slug personalizado</label>
                <input
                    id="custom_slug"
                    type="text"
                    name="custom_slug"
                    value="{{ old('custom_slug') }}"
                    minlength="3"
                    maxlength="40"
                    pattern="[a-z0-9][a-z0-9-]{1,38}[a-z0-9]"
                    placeholder="nome-da-campanha"
                    required>
                <div class="hint">Use minúsculas, números e hífen. Não comece nem termine com hífen.</div>
            </div>

            <div class="field">
                <label for="valid_until">Expira em</label>
                <input id="valid_until" type="datetime-local" name="valid_until" value="{{ old('valid_until') }}">
                <div class="hint">Opcional. Deixe vazio para manter a validade padrão do fluxo premium.</div>
            </div>

            <div class="actions">
                <button type="submit" class="primary">Criar link premium</button>
                <a class="button ghost" href="{{ route('links.index') }}">Cancelar</a>
            </div>
        </form>
    </section>
@endsection
