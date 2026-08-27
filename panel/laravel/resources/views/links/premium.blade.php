@extends('layouts.app')

@section('title', 'MElink | Criar campanha')

@section('content')
    <div class="hero">
        <section class="hero-card">
            <span class="eyebrow">Fluxo premium · campanhas</span>
            <h1 class="page-title">Crie um link que trabalha por você</h1>
            <p class="page-subtitle">
                Use sua marca, organize a campanha e acompanhe o resultado. O destino continua editável no seu fluxo premium,
                enquanto os parâmetros UTM ajudam a identificar de onde veio cada visita.
            </p>

            <div class="hero-actions">
                <a class="button secondary" href="{{ route('links.index') }}">Voltar para links</a>
                <a class="button secondary" href="{{ route('domains.index') }}">Gerenciar domínios</a>
            </div>
        </section>

        <aside class="hero-side">
            <div class="card compact">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Recursos ativos</h2>
                        <p class="meta">Construção de campanha em um único fluxo.</p>
                    </div>
                    <span class="badge success">Premium</span>
                </div>

                <ul class="list">
                    <li><span class="label">Slug e domínio</span><span class="value">Personalizáveis</span></li>
                    <li><span class="label">UTM</span><span class="value">5 parâmetros</span></li>
                    <li><span class="label">Analytics</span><span class="value">Por link</span></li>
                </ul>
            </div>
        </aside>
    </div>

    @if ($errors->any())
        <div class="alert danger" role="alert">
            <strong>Revise os dados da campanha.</strong>
            <ul class="list" style="margin-top: 8px;">
                @foreach ($errors->all() as $error)
                    <li><span class="label">Atenção</span><span class="value">{{ $error }}</span></li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="card">
        <div class="section-head">
            <div>
                <h2>Identidade do link</h2>
                <p>Comece com uma URL clara. Depois, escolha como ela será apresentada para o público.</p>
            </div>
        </div>

        <form method="post" action="{{ route('links.premium.store') }}" class="form-grid">
            @csrf

            <div class="field" style="grid-column: 1 / -1;">
                <label for="long_url">URL de destino</label>
                <input id="long_url" type="url" name="long_url" value="{{ old('long_url') }}"
                       maxlength="2048" placeholder="https://suaempresa.com/campanha" required>
                @error('long_url')<div class="hint" style="color:#b42318;">{{ $message }}</div>@enderror
            </div>

            <div class="field">
                <label for="custom_slug">Slug personalizado</label>
                <input id="custom_slug" type="text" name="custom_slug" value="{{ old('custom_slug') }}"
                       minlength="3" maxlength="40" pattern="[a-z0-9][a-z0-9-]{1,38}[a-z0-9]"
                       placeholder="promo-verao" required>
                <div class="hint">Minúsculas, números e hífen. Ex.: <code>promo-verao</code>.</div>
                @error('custom_slug')<div class="hint" style="color:#b42318;">{{ $message }}</div>@enderror
            </div>

            <div class="field">
                <label for="domain">Domínio da marca</label>
                <select id="domain" name="domain">
                    <option value="">Usar {{ config('shlink.default_domain') }}</option>
                    @foreach ($customDomains as $customDomain)
                        <option value="{{ $customDomain->domain }}" @selected(old('domain') === $customDomain->domain)>
                            {{ $customDomain->domain }}
                        </option>
                    @endforeach
                </select>
                <div class="hint">Somente domínios DNS verificados e ativos aparecem aqui.</div>
                @error('domain')<div class="hint" style="color:#b42318;">{{ $message }}</div>@enderror
            </div>

            <div class="field">
                <label for="title">Nome interno da campanha</label>
                <input id="title" type="text" name="title" value="{{ old('title') }}"
                       maxlength="120" placeholder="Lançamento de verão">
                <div class="hint">Ajuda seu time a encontrar o link depois.</div>
            </div>

            <div class="field">
                <label for="tags">Tags</label>
                <input id="tags" type="text" name="tags" value="{{ old('tags') }}"
                       maxlength="500" placeholder="instagram, verão, produto">
                <div class="hint">Separe por vírgula. Até 10 tags simples.</div>
            </div>

            <div class="field" style="grid-column: 1 / -1;">
                <h3 style="margin: 4px 0 0;">Rastreamento de campanha</h3>
                <p class="meta">Use UTMs para comparar anúncios, canais e criativos no seu analytics.</p>
            </div>

            <div class="field">
                <label for="utm_source">UTM source</label>
                <input id="utm_source" type="text" name="utm_source" value="{{ old('utm_source') }}"
                       maxlength="100" placeholder="instagram">
            </div>

            <div class="field">
                <label for="utm_medium">UTM medium</label>
                <input id="utm_medium" type="text" name="utm_medium" value="{{ old('utm_medium') }}"
                       maxlength="100" placeholder="paid-social">
            </div>

            <div class="field">
                <label for="utm_campaign">UTM campaign</label>
                <input id="utm_campaign" type="text" name="utm_campaign" value="{{ old('utm_campaign') }}"
                       maxlength="100" placeholder="lancamento-verao">
            </div>

            <div class="field">
                <label for="utm_term">UTM term</label>
                <input id="utm_term" type="text" name="utm_term" value="{{ old('utm_term') }}"
                       maxlength="100" placeholder="lookalike-25-34">
            </div>

            <div class="field">
                <label for="utm_content">UTM content</label>
                <input id="utm_content" type="text" name="utm_content" value="{{ old('utm_content') }}"
                       maxlength="100" placeholder="criativo-a">
            </div>

            <div class="field">
                <label for="valid_until">Expira em</label>
                <input id="valid_until" type="datetime-local" name="valid_until" value="{{ old('valid_until') }}">
                <div class="hint">Opcional. O limite máximo é de um ano.</div>
            </div>

            <div class="field" style="display:flex; align-items:center; gap:10px;">
                <input id="forward_query" type="checkbox" name="forward_query" value="1" @checked(old('forward_query'))>
                <label for="forward_query" style="margin:0;">Encaminhar parâmetros da URL compartilhada</label>
                <div class="hint">Útil para links que recebem query dinâmica.</div>
            </div>

            <div class="actions" style="grid-column: 1 / -1;">
                <button type="submit" class="primary">Criar campanha</button>
                <a class="button ghost" href="{{ route('links.index') }}">Cancelar</a>
            </div>
        </form>
    </section>
@endsection
