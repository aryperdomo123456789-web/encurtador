@extends('layouts.app')

@section('title', $plan ? 'MElink | Editar plano' : 'MElink | Criar plano')
@section('meta_description', 'Configuração owner-only de plano comercial do MElink.')

@section('content')
    <div class="hero">
        <section class="hero-card">
            <span class="eyebrow">Catálogo comercial</span>
            <h1 class="page-title">{{ $plan ? 'Editar plano' : 'Criar plano' }}</h1>
            <p class="page-subtitle">
                Defina o que o cliente compra e o que o produto entrega. Valores ficam em centavos no servidor;
                o checkout nunca aceita preço enviado pelo navegador.
            </p>
            <div class="hero-actions">
                <a class="button secondary" href="{{ route('admin.plans.index') }}">Voltar ao catálogo</a>
            </div>
        </section>
        <aside class="hero-side">
            <div class="card compact">
                <h2 class="card-title">Regra de segurança</h2>
                <p class="meta" style="margin-top:8px;">O Price ID é apenas um vínculo público. Chaves secretas e webhook ficam exclusivamente no ambiente seguro do aaPanel.</p>
            </div>
        </aside>
    </div>

    <form method="POST" action="{{ $formAction }}" class="section">
        @csrf
        @if($formMethod !== 'POST')
            @method($formMethod)
        @endif

        <section class="card">
            <div class="section-head">
                <div>
                    <h2>Identidade comercial</h2>
                    <p>Use um código estável; ele será usado em integrações e relatórios.</p>
                </div>
            </div>
            <div class="grid cards-2">
                <div class="field">
                    <label for="name">Nome do plano</label>
                    <input id="name" name="name" type="text" maxlength="80" required value="{{ old('name', $plan?->name) }}">
                </div>
                <div class="field">
                    <label for="code">Código técnico</label>
                    <input id="code" name="code" type="text" maxlength="32" pattern="[a-z][a-z0-9_-]{1,31}" required value="{{ old('code', $plan?->code) }}" {{ $plan ? 'readonly' : '' }}>
                    <div class="hint">Somente letras minúsculas, números, hífen e sublinhado. Ex.: <code>start</code>.</div>
                </div>
                <div class="field">
                    <label for="marketing_label">Destaque comercial</label>
                    <input id="marketing_label" name="marketing_label" type="text" maxlength="120" value="{{ old('marketing_label', $plan?->marketing_label) }}" placeholder="Para creators e pequenas lojas">
                </div>
                <div class="field">
                    <label for="sort_order">Ordem de exibição</label>
                    <input id="sort_order" name="sort_order" type="number" min="0" max="65535" required value="{{ old('sort_order', $plan?->sort_order ?? 10) }}">
                </div>
                <div class="field" style="grid-column:1 / -1;">
                    <label for="description">Descrição</label>
                    <textarea id="description" name="description" rows="3" maxlength="255">{{ old('description', $plan?->description) }}</textarea>
                </div>
            </div>
        </section>

        <section class="card section">
            <div class="section-head">
                <div>
                    <h2>Preço e capacidade</h2>
                    <p>O limite de cliques é um pool mensal somado por conta/workspace, não por link.</p>
                </div>
            </div>
            <div class="grid cards-3">
                <div class="field">
                    <label for="monthly_price_cents">Preço mensal (centavos)</label>
                    <input id="monthly_price_cents" name="monthly_price_cents" type="number" min="0" max="100000000" required value="{{ old('monthly_price_cents', $plan?->monthly_price_cents ?? 0) }}">
                    <div class="hint">R$ 19,90 = <code>1990</code>.</div>
                </div>
                <div class="field">
                    <label for="currency">Moeda</label>
                    <input id="currency" name="currency" type="text" value="{{ old('currency', $plan?->currency ?? 'BRL') }}" maxlength="3" readonly>
                </div>
                <div class="field">
                    <label for="monthly_short_url_limit">Links / mês</label>
                    <input id="monthly_short_url_limit" name="monthly_short_url_limit" type="number" min="0" max="100000000" value="{{ old('monthly_short_url_limit', $plan?->monthly_short_url_limit) }}">
                    <div class="hint">Vazio = ilimitado.</div>
                </div>
                <div class="field">
                    <label for="monthly_click_limit">Cliques / mês</label>
                    <input id="monthly_click_limit" name="monthly_click_limit" type="number" min="0" max="1000000000" value="{{ old('monthly_click_limit', $plan?->monthly_click_limit) }}">
                    <div class="hint">Vazio = ilimitado.</div>
                </div>
                <div class="field">
                    <label for="custom_domain_limit">Domínios próprios</label>
                    <input id="custom_domain_limit" name="custom_domain_limit" type="number" min="0" max="10000" required value="{{ old('custom_domain_limit', $plan?->custom_domain_limit ?? 0) }}">
                </div>
            </div>
        </section>

        <section class="card section">
            <div class="section-head">
                <div>
                    <h2>Entitlements</h2>
                    <p>Estas flags dirigem o que cada plano pode usar no produto.</p>
                </div>
            </div>
            <div class="grid cards-3">
                @foreach([
                    'allow_custom_slug' => 'Slug customizado',
                    'allow_custom_domain' => 'Domínio próprio',
                    'allow_custom_expiration' => 'Expiração customizada',
                    'allow_lifetime_links' => 'Links vitalícios',
                    'is_free' => 'Plano gratuito',
                    'is_featured' => 'Plano em destaque',
                    'is_public' => 'Visível no catálogo',
                    'is_active' => 'Plano ativo',
                ] as $field => $label)
                    <label class="card compact" style="display:flex;align-items:center;gap:12px;cursor:pointer;">
                        <input type="hidden" name="{{ $field }}" value="0">
                        <input type="checkbox" name="{{ $field }}" value="1" {{ old($field, $plan?->{$field} ?? ($field === 'is_active' || $field === 'is_public' ? true : false)) ? 'checked' : '' }}>
                        <span><strong>{{ $label }}</strong></span>
                    </label>
                @endforeach
            </div>
        </section>

        <section class="card section">
            <div class="section-head">
                <div>
                    <h2>Vínculo Stripe</h2>
                    <p>Opcional no catálogo local. O preenchimento deve usar um Price/Product já criado no modo correto.</p>
                </div>
            </div>
            <div class="grid cards-2">
                <div class="field">
                    <label for="stripe_product_id">Stripe Product ID</label>
                    <input id="stripe_product_id" name="stripe_product_id" type="text" maxlength="128" pattern="prod_[A-Za-z0-9]+" value="{{ old('stripe_product_id', $plan?->stripe_product_id) }}" placeholder="prod_...">
                </div>
                <div class="field">
                    <label for="stripe_price_id">Stripe Price ID mensal</label>
                    <input id="stripe_price_id" name="stripe_price_id" type="text" maxlength="128" pattern="price_[A-Za-z0-9]+" value="{{ old('stripe_price_id', $plan?->stripe_price_id) }}" placeholder="price_...">
                </div>
            </div>
        </section>

        <div class="hero-actions">
            <button class="button primary" type="submit">{{ $plan ? 'Salvar alterações' : 'Criar plano' }}</button>
            <a class="button secondary" href="{{ route('admin.plans.index') }}">Cancelar</a>
        </div>
    </form>
@endsection
