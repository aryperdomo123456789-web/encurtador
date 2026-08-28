@extends('layouts.app')

@section('title', 'MElink | Marca & Identidade')
@section('meta_description', 'Gerencie a identidade visual, logo, favicon e imagem social do MElink.')

@section('content')
    <div class="hero">
        <section class="hero-card">
            <span class="eyebrow">Admin / Dono</span>
            <h1 class="page-title">Marca &amp; identidade</h1>
            <p class="page-subtitle">
                Controle a presença do MElink no painel, no login e nas prévias de compartilhamento. O fallback padrão
                continua ativo sempre que um slot não tiver um arquivo válido.
            </p>

            <div class="hero-actions">
                <a class="button secondary" href="{{ route('admin.dashboard') }}">Voltar ao admin</a>
                <a class="button secondary" href="{{ route('dashboard') }}">Ver painel</a>
            </div>
        </section>

        <aside class="hero-side">
            <div class="card compact">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Sistema de identidade</h2>
                        <p class="meta">Uma fonte de verdade para todas as superfícies.</p>
                    </div>
                    <span class="badge success">Ativo</span>
                </div>
                <div class="stack">
                    <div class="mini-card brand-summary-row">
                        <span class="brand-summary-icon">Aa</span>
                        <div>
                            <strong>Light / dark</strong>
                            <span class="meta">Logo adaptável ao tema do dispositivo</span>
                        </div>
                    </div>
                    <div class="mini-card brand-summary-row">
                        <span class="brand-summary-icon">↗</span>
                        <div>
                            <strong>Compartilhamento</strong>
                            <span class="meta">Imagem social para WhatsApp e Telegram</span>
                        </div>
                    </div>
                </div>
            </div>
        </aside>
    </div>

    <form method="POST" action="{{ route('admin.brand.update') }}" enctype="multipart/form-data">
        @csrf

        <section class="section-head section-head-spaced">
            <div>
                <h2>Assinatura visual</h2>
                <p>Use arquivos limpos, com boa margem e fundo transparente quando possível.</p>
            </div>
            <span class="badge info">{{ $branding->hasCustomLogo('light') || $branding->hasCustomLogo('dark') ? 'Personalizada' : 'Padrão' }}</span>
        </section>

        <section class="grid cards-2 section">
            <div class="card brand-slot-card">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Logo light</h2>
                        <p class="meta">Para fundos claros, topo e login.</p>
                    </div>
                    <span class="badge {{ $branding->hasCustomLogo('light') ? 'success' : 'muted' }}">{{ $branding->hasCustomLogo('light') ? 'Personalizada' : 'Fallback' }}</span>
                </div>

                <div class="brand-preview brand-preview-light">
                    <picture>
                        <source media="(prefers-color-scheme: dark)" srcset="{{ $branding->logoUrl('dark') }}">
                        <img src="{{ $branding->logoUrl('light') }}" alt="Prévia da logo light" loading="lazy">
                    </picture>
                </div>

                <div class="field">
                    <label for="logo_light_image">Substituir logo light</label>
                    <input id="logo_light_image" type="file" name="logo_light_image" accept="image/png,image/jpeg,image/webp">
                    <p class="hint">PNG, JPG ou WebP até 5 MB. A versão antiga só é removida após o novo arquivo ser aceito.</p>
                </div>

                <label class="remember brand-reset">
                    <input type="checkbox" name="reset_logo_light" value="1">
                    Usar fallback padrão neste slot
                </label>
            </div>

            <div class="card brand-slot-card brand-slot-dark">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Logo dark</h2>
                        <p class="meta">Para dispositivos e superfícies escuras.</p>
                    </div>
                    <span class="badge {{ $branding->hasCustomLogo('dark') ? 'success' : 'muted' }}">{{ $branding->hasCustomLogo('dark') ? 'Personalizada' : 'Fallback light' }}</span>
                </div>

                <div class="brand-preview brand-preview-dark">
                    <img src="{{ $branding->logoUrl('dark') }}" alt="Prévia da logo dark" loading="lazy">
                </div>

                <div class="field">
                    <label for="logo_dark_image">Substituir logo dark</label>
                    <input id="logo_dark_image" type="file" name="logo_dark_image" accept="image/png,image/jpeg,image/webp">
                    <p class="hint">Se ficar vazio, o MElink usa a logo light; se ambas faltarem, usa o fallback padrão.</p>
                </div>

                <label class="remember brand-reset">
                    <input type="checkbox" name="reset_logo_dark" value="1">
                    Usar logo light como fallback
                </label>
            </div>
        </section>

        <section class="section-head section-head-spaced">
            <div>
                <h2>Superfícies de distribuição</h2>
                <p>Esses arquivos aparecem fora do painel e precisam continuar legíveis em tamanhos pequenos.</p>
            </div>
        </section>

        <section class="grid cards-2 section">
            <div class="card brand-slot-card">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Favicon</h2>
                        <p class="meta">Aba do navegador e favorito.</p>
                    </div>
                    <span class="badge {{ $branding->hasCustomFavicon() ? 'success' : 'muted' }}">{{ $branding->hasCustomFavicon() ? 'Personalizado' : 'Fallback' }}</span>
                </div>

                <div class="brand-preview brand-preview-icon">
                    <img src="{{ $branding->faviconUrl() }}" alt="Prévia do favicon" loading="lazy">
                </div>

                <div class="field">
                    <label for="favicon_image">Substituir favicon</label>
                    <input id="favicon_image" type="file" name="favicon_image" accept="image/png,image/jpeg,image/webp">
                    <p class="hint">Prefira uma arte quadrada, simples e reconhecível em 32 px.</p>
                </div>

                <label class="remember brand-reset">
                    <input type="checkbox" name="reset_favicon" value="1">
                    Restaurar favicon padrão
                </label>
            </div>

            <div class="card brand-slot-card">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Imagem social</h2>
                        <p class="meta">Prévia de links em mensageiros e redes.</p>
                    </div>
                    <span class="badge {{ $branding->hasCustomSocialImage() ? 'success' : 'muted' }}">{{ $branding->hasCustomSocialImage() ? 'Personalizada' : 'Fallback' }}</span>
                </div>

                <div class="brand-preview brand-preview-social">
                    <img src="{{ $branding->socialImageUrl() }}" alt="Prévia da imagem social" loading="lazy">
                </div>

                <div class="field">
                    <label for="social_image">Substituir imagem social</label>
                    <input id="social_image" type="file" name="social_image" accept="image/png,image/jpeg,image/webp">
                    <p class="hint">Use uma composição horizontal com texto legível e área segura para recorte.</p>
                </div>

                <label class="remember brand-reset">
                    <input type="checkbox" name="reset_social_image" value="1">
                    Restaurar imagem social padrão
                </label>
            </div>
        </section>

        <section class="card section save-panel">
            <div>
                <span class="eyebrow">Publicação controlada</span>
                <h2>Salvar identidade</h2>
                <p class="meta">As alterações são aplicadas no painel, login, favicon e metadados sociais após salvar.</p>
            </div>
            <div class="hero-actions">
                <button type="submit" class="primary">Salvar alterações</button>
                <a class="button secondary" href="{{ route('admin.dashboard') }}">Cancelar</a>
            </div>
        </section>
    </form>
@endsection
