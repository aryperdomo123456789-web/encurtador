@extends('layouts.app')

@section('title', 'MElink | Marca do painel')
@section('meta_description', 'Troque logo, favicon e imagem de compartilhamento do painel.')

@section('content')
    <div class="hero">
        <section class="hero-card">
            <span class="eyebrow">Admin do dono</span>
            <h1 class="page-title">Marca do painel</h1>
            <p class="page-subtitle">
                Ajuste aqui a identidade visual que aparece no topo, no favicon do navegador e nas prévias de
                WhatsApp, Telegram e demais apps de compartilhamento.
            </p>

            <div class="hero-actions">
                <a class="button secondary" href="{{ route('admin.users.index') }}">Voltar aos usuários</a>
                <a class="button secondary" href="{{ route('dashboard') }}">Ver painel</a>
            </div>
        </section>

        <aside class="hero-side">
            <div class="card compact">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Uso atual</h2>
                        <p class="meta">O mesmo arquivo pode ser reutilizado em todas as áreas.</p>
                    </div>
                    <span class="badge info">Preview</span>
                </div>

                <div class="stack" style="gap: 14px;">
                    <div class="mini-card" style="display:flex; align-items:center; gap:12px;">
                        <img src="{{ $branding->logoUrl() }}" alt="Logo atual" style="width:56px; height:56px; border-radius:18px; object-fit:cover; background:#fff;">
                        <div>
                            <strong style="display:block;">Logo</strong>
                            <span class="meta">Topo e login</span>
                        </div>
                    </div>
                    <div class="mini-card" style="display:flex; align-items:center; gap:12px;">
                        <img src="{{ $branding->faviconUrl() }}" alt="Favicon atual" style="width:40px; height:40px; border-radius:12px; object-fit:cover; background:#fff;">
                        <div>
                            <strong style="display:block;">Favicon</strong>
                            <span class="meta">Aba do navegador</span>
                        </div>
                    </div>
                    <div class="mini-card" style="display:flex; align-items:center; gap:12px;">
                        <img src="{{ $branding->socialImageUrl() }}" alt="Imagem social atual" style="width:72px; height:72px; border-radius:18px; object-fit:cover; background:#fff;">
                        <div>
                            <strong style="display:block;">Social image</strong>
                            <span class="meta">WhatsApp, Telegram e outras prévias</span>
                        </div>
                    </div>
                </div>
            </div>
        </aside>
    </div>

    <form method="POST" action="{{ route('admin.branding.update') }}" enctype="multipart/form-data">
        @csrf

        <section class="grid cards-3 section">
            <div class="card">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Logo</h2>
                        <p class="meta">Usada no topo e nas telas de entrada.</p>
                    </div>
                    <span class="badge info">Atual</span>
                </div>

                <div class="mini-card" style="margin-bottom:16px;">
                    <img src="{{ $branding->logoUrl() }}" alt="Logo atual" style="width:100%; max-width:180px; height:auto; border-radius:18px; background:#fff; object-fit:cover;">
                </div>

                <div class="field">
                    <label for="logo_image">Nova logo</label>
                    <input id="logo_image" type="file" name="logo_image" accept="image/*">
                    <p class="hint">Quadrada ou quase quadrada funciona melhor.</p>
                </div>

                <label class="remember" style="margin-top:10px;">
                    <input type="checkbox" name="reset_logo" value="1">
                    Restaurar para o padrão
                </label>
            </div>

            <div class="card">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Favicon</h2>
                        <p class="meta">Ícone da aba e favorito do navegador.</p>
                    </div>
                    <span class="badge info">Atual</span>
                </div>

                <div class="mini-card" style="margin-bottom:16px;">
                    <img src="{{ $branding->faviconUrl() }}" alt="Favicon atual" style="width:72px; height:72px; border-radius:18px; background:#fff; object-fit:cover;">
                </div>

                <div class="field">
                    <label for="favicon_image">Novo favicon</label>
                    <input id="favicon_image" type="file" name="favicon_image" accept="image/*">
                    <p class="hint">Pode ser o mesmo arquivo da logo, se quiser.</p>
                </div>

                <label class="remember" style="margin-top:10px;">
                    <input type="checkbox" name="reset_favicon" value="1">
                    Restaurar para o padrão
                </label>
            </div>

            <div class="card">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Imagem social</h2>
                        <p class="meta">Prévia para WhatsApp, Telegram e redes.</p>
                    </div>
                    <span class="badge info">Atual</span>
                </div>

                <div class="mini-card" style="margin-bottom:16px;">
                    <img src="{{ $branding->socialImageUrl() }}" alt="Imagem social atual" style="width:100%; max-width:240px; height:auto; border-radius:18px; background:#fff; object-fit:cover;">
                </div>

                <div class="field">
                    <label for="social_image">Nova imagem social</label>
                    <input id="social_image" type="file" name="social_image" accept="image/*">
                    <p class="hint">Essa é a imagem que links compartilham em apps e mensageiros.</p>
                </div>

                <label class="remember" style="margin-top:10px;">
                    <input type="checkbox" name="reset_social_image" value="1">
                    Restaurar para o padrão
                </label>
            </div>
        </section>

        <section class="card section">
            <div class="section-head">
                <div>
                    <h2>Salvar alterações</h2>
                    <p>Troque uma imagem por vez ou mantenha o padrão do laboratório.</p>
                </div>
            </div>

            <div class="hero-actions">
                <button type="submit" class="primary">Salvar marca</button>
                <a class="button secondary" href="{{ route('admin.users.index') }}">Cancelar</a>
            </div>
        </section>
    </form>
@endsection
