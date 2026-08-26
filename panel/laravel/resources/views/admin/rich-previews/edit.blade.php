@extends('layouts.app')

@section('title', 'MElink | Editar Rich Preview')
@section('meta_description', 'Edite a imagem, o título, a descrição e o destino do seu rich preview.')

@section('content')
    <div class="hero">
        <section class="hero-card">
            <span class="eyebrow">Admin do dono</span>
            <h1 class="page-title">Editar rich preview</h1>
            <p class="page-subtitle">
                Ajuste o conteúdo compartilhado e acompanhe o link público gerado para WhatsApp, Telegram e redes.
            </p>

            <div class="hero-actions">
                <a class="button secondary" href="{{ route('admin.rich-previews.index') }}">Voltar à lista</a>
                <button
                    type="button"
                    class="button secondary"
                    data-copy-link="{{ $richPreview->previewUrl() }}"
                    data-copy-label="Copiar link"
                >Copiar link</button>
                <a class="button secondary" href="{{ $richPreview->previewUrl() }}" target="_blank" rel="noreferrer">Abrir prévia</a>
                <form method="POST" action="{{ route('admin.rich-previews.duplicate', $richPreview) }}" style="display:inline;">
                    @csrf
                    <button type="submit" class="button secondary">Duplicar</button>
                </form>
            </div>
        </section>

        <aside class="hero-side">
            <div class="card compact">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Links</h2>
                        <p class="meta">Veja a página pública e o destino final.</p>
                    </div>
                    <span class="badge info">Live</span>
                </div>

                <ul class="list">
                    <li>
                        <span class="label">Prévia pública</span>
                        <span class="value" style="font-size:0.82rem; word-break:break-all;">{{ $richPreview->previewUrl() }}</span>
                    </li>
                    <li>
                        <span class="label">Clique final</span>
                        <span class="value" style="font-size:0.82rem; word-break:break-all;">{{ $richPreview->goUrl() }}</span>
                    </li>
                    <li>
                        <span class="label">ID / criado</span>
                        <span class="value" style="font-size:0.82rem;">
                            #{{ $richPreview->id }}<br>
                            {{ $richPreview->created_at?->format('d/m/Y H:i') ?? 'n/d' }}<br>
                            {{ $richPreview->creator?->name ?? $richPreview->user?->name ?? 'Sistema' }}
                        </span>
                    </li>
                    <li>
                        <span class="label">Atualizado</span>
                        <span class="value" style="font-size:0.82rem;">
                            {{ $richPreview->updated_at?->format('d/m/Y H:i') ?? 'n/d' }}<br>
                            {{ $richPreview->updater?->name ?? $richPreview->creator?->name ?? $richPreview->user?->name ?? 'Sistema' }}
                        </span>
                    </li>
                </ul>
            </div>
        </aside>
    </div>

    @include('admin.rich-previews.partials.form', [
        'richPreview' => $richPreview,
        'formAction' => route('admin.rich-previews.update', $richPreview),
        'formMethod' => 'PUT',
        'buttonLabel' => 'Salvar rich preview',
    ])

    <section class="card section">
        <div class="section-head">
            <div>
                <h2>Remover card</h2>
                <p>Excluir apaga apenas o registro e a imagem enviada no painel.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.rich-previews.destroy', $richPreview) }}" onsubmit="return confirm('Tem certeza que deseja excluir este rich preview?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="button danger">Excluir rich preview</button>
        </form>
    </section>

    <script>
        (() => {
            const button = document.querySelector('[data-copy-link]');

            if (!button || !navigator.clipboard?.writeText) {
                return;
            }

            const defaultLabel = button.getAttribute('data-copy-label') || button.textContent.trim();

            button.addEventListener('click', async () => {
                const url = button.getAttribute('data-copy-link');

                if (!url) {
                    return;
                }

                try {
                    await navigator.clipboard.writeText(url);
                    button.textContent = 'Copiado';
                    window.setTimeout(() => {
                        button.textContent = defaultLabel;
                    }, 1200);
                } catch (error) {
                    console.error('Falha ao copiar o link da rich preview.', error);
                }
            });
        })();
    </script>
@endsection
