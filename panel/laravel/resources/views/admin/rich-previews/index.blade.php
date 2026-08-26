@extends('layouts.app')

@section('title', 'MElink | Rich Preview')
@section('meta_description', 'Crie páginas de compartilhamento com Open Graph para WhatsApp, Telegram e redes.')

@section('content')
    <div class="hero">
        <section class="hero-card">
            <span class="eyebrow">Admin do dono</span>
            <h1 class="page-title">Rich Preview</h1>
            <p class="page-subtitle">
                Crie páginas de compartilhamento inteligentes com imagem, título e descrição próprios. O link
                curto abre a prévia e o clique leva ao destino final.
            </p>

            <div class="hero-actions">
                <a class="button primary" href="{{ route('admin.rich-previews.create') }}">Novo rich preview</a>
                <a class="button secondary" href="{{ route('admin.users.index') }}">Voltar aos usuários</a>
            </div>
        </section>

        <aside class="hero-side">
            <div class="card compact">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Resumo</h2>
                        <p class="meta">Indicadores rápidos dos cards publicados.</p>
                    </div>
                    <span class="badge info">OG</span>
                </div>

                <ul class="list">
                    <li><span class="label">Total</span><span class="value">{{ $summary['total'] }}</span></li>
                    <li><span class="label">Ativos</span><span class="value">{{ $summary['active'] }}</span></li>
                    <li><span class="label">Cliques</span><span class="value">{{ $summary['clicks'] }}</span></li>
                    <li><span class="label">Campanhas</span><span class="value">{{ $summary['campaigns'] }}</span></li>
                    <li><span class="label">Categorias</span><span class="value">{{ $summary['categories'] }}</span></li>
                </ul>
            </div>
        </aside>
    </div>

    <section class="card section">
        <form method="get" action="{{ route('admin.rich-previews.index') }}" class="form-grid" style="margin-bottom: 16px;">
            <div class="field">
                <label for="campaign">Campanha</label>
                <input id="campaign" type="search" name="campaign" value="{{ $campaign }}" placeholder="filtrar por campanha">
            </div>
            <div class="field">
                <label for="category">Categoria</label>
                <input id="category" type="search" name="category" value="{{ $category }}" placeholder="filtrar por categoria">
            </div>
            <div class="actions">
                <button type="submit" class="primary">Filtrar</button>
                @if($campaign !== '' || $category !== '')
                    <a class="button ghost" href="{{ route('admin.rich-previews.index') }}">Limpar</a>
                @endif
            </div>
        </form>

        <div class="section-head">
            <div>
                <h2>Cards cadastrados</h2>
                <p>Abra um item para editar a imagem, o texto e o destino final.</p>
            </div>
        </div>

        @if ($richPreviews->isEmpty())
            <div class="mini-card">
                Ainda não existe nenhum rich preview criado.
            </div>
        @else
            <div class="table-panel" style="margin-top: 14px;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Card</th>
                            <th>Campanha / Categoria</th>
                            <th>Slug público</th>
                            <th>Destino</th>
                            <th>Status</th>
                            <th>Métricas</th>
                            <th>Auditoria</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($richPreviews as $item)
                            <tr>
                                <td>
                                    <div style="font-weight:700;">{{ $item->title }}</div>
                                    <div class="meta">{{ $item->excerpt() }}</div>
                                </td>
                                <td>
                                    <div class="meta">{{ $item->campaignLabel() !== '' ? $item->campaignLabel() : 'Sem campanha' }}</div>
                                    <div class="meta">{{ $item->categoryLabel() !== '' ? $item->categoryLabel() : 'Sem categoria' }}</div>
                                </td>
                                <td>
                                    <div style="font-weight:700;">{{ $item->slug }}</div>
                                    <div class="meta"><a href="{{ $item->previewUrl() }}" target="_blank" rel="noreferrer">Abrir prévia</a></div>
                                </td>
                                <td class="meta" style="max-width:280px; word-break:break-word;">
                                    {{ $item->destination_url }}
                                </td>
                                <td>
                                    <span class="badge {{ $item->is_active ? 'info' : 'muted' }}">
                                        {{ $item->is_active ? 'Ativo' : 'Inativo' }}
                                    </span>
                                </td>
                                <td>
                                    <div><strong>{{ $item->click_count }}</strong> cliques</div>
                                    <div class="meta">Último: {{ $item->last_clicked_at?->format('d/m/Y H:i') ?? 'nunca' }}</div>
                                </td>
                                <td>
                                    <div style="font-weight:700;">#{{ $item->id }}</div>
                                    <div class="meta">Criado: {{ $item->created_at?->format('d/m/Y H:i') ?? 'n/d' }}</div>
                                    <div class="meta">Por: {{ $item->creator?->name ?? $item->user?->name ?? 'Sistema' }}</div>
                                    <div class="meta">Atualizado: {{ $item->updated_at?->format('d/m/Y H:i') ?? 'n/d' }}</div>
                                    <div class="meta">Por: {{ $item->updater?->name ?? $item->creator?->name ?? $item->user?->name ?? 'Sistema' }}</div>
                                </td>
                                <td>
                                    <div class="actions" style="justify-content:flex-end;">
                                        <button
                                            type="button"
                                            class="button secondary"
                                            data-copy-link="{{ $item->previewUrl() }}"
                                            data-copy-label="Copiar link"
                                        >Copiar link</button>
                                        <form method="POST" action="{{ route('admin.rich-previews.duplicate', $item) }}" style="display:inline;">
                                            @csrf
                                            <button type="submit" class="button secondary">Duplicar</button>
                                        </form>
                                        <a class="button secondary" href="{{ route('admin.rich-previews.edit', $item) }}">Editar</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <script>
        (() => {
            document.querySelectorAll('[data-copy-link]').forEach((button) => {
                if (!navigator.clipboard?.writeText) {
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
            });
        })();
    </script>
@endsection
