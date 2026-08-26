@extends('layouts.app')

@section('title', 'MElink | Analytics ' . $shortCode)

@section('content')
    @php
        $items = collect(data_get($visits, 'visits') ?? data_get($visits, 'data') ?? []);
        $pagination = data_get($visits, 'pagination', []);
        $totalItems = (int) data_get($pagination, 'totalItems', $items->count());
        $page = (int) data_get($pagination, 'currentPage', 1);
        $itemsPerPage = (int) data_get($pagination, 'itemsPerPage', data_get($pagination, 'limit', max(1, $items->count())));
        $title = data_get($link->shlink_payload, 'title') ?: 'Campanha sem nome';
        $tags = collect(data_get($link->shlink_payload, 'tags', []));
        $destinationQuery = [];
        parse_str((string) parse_url((string) $link->long_url, PHP_URL_QUERY), $destinationQuery);
        $utmCount = collect(['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'])
            ->filter(fn (string $key): bool => isset($destinationQuery[$key]) && $destinationQuery[$key] !== '')
            ->count();
        $countries = $items
            ->map(fn ($item) => data_get($item, 'visitLocation.countryName') ?? data_get($item, 'countryName'))
            ->filter()
            ->countBy()
            ->sortDesc()
            ->take(3);
    @endphp

    <div class="hero">
        <section class="hero-card">
            <span class="eyebrow">Performance da campanha</span>
            <h1 class="page-title">{{ $title }}</h1>
            <p class="page-subtitle">
                <code>{{ $shortCode }}</code> em <strong>{{ $link->domain }}</strong>. Veja o que aconteceu depois que seu público clicou.
            </p>

            <div class="hero-actions">
                <a class="button secondary" href="{{ route('links.index') }}">Voltar para links</a>
                @if ($link->shlink_short_url)
                    <a class="button primary" href="{{ $link->shlink_short_url }}" target="_blank" rel="noopener">Abrir link</a>
                @endif
            </div>
        </section>

        <aside class="hero-side">
            <div class="card compact">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Resumo executivo</h2>
                        <p class="meta">Filtro atual aplicado ao motor.</p>
                    </div>
                    <span class="badge {{ $analyticsError ? 'warning' : 'success' }}">
                        {{ $analyticsError ? 'Aguardando dados' : 'Atualizado' }}
                    </span>
                </div>

                <div class="grid cards-2" style="gap:10px;">
                    <div class="mini-card"><strong>{{ number_format($totalItems, 0, ',', '.') }}</strong><span>visitas</span></div>
                    <div class="mini-card"><strong>{{ number_format($items->count(), 0, ',', '.') }}</strong><span>nesta página</span></div>
                    <div class="mini-card"><strong>{{ $utmCount }}</strong><span>UTMs ativas</span></div>
                    <div class="mini-card"><strong>{{ $countries->count() }}</strong><span>países visíveis</span></div>
                </div>
            </div>
        </aside>
    </div>

    @if ($analyticsError)
        <div class="alert warning" role="status">
            <strong>{{ $analyticsError }}</strong>
            <div class="meta" style="margin-top:6px;">A falha do analytics não interrompe o redirecionamento do link.</div>
        </div>
    @endif

    <section class="card section">
        <div class="section-head">
            <div>
                <h2>Filtrar período</h2>
                <p>Compare campanhas sem despejar o payload técnico na tela.</p>
            </div>
        </div>
        <form method="get" action="{{ route('analytics.show', ['shortCode' => $shortCode]) }}" class="form-grid">
            <div class="field">
                <label for="startDate">De</label>
                <input id="startDate" type="date" name="startDate" value="{{ $startDate }}">
            </div>
            <div class="field">
                <label for="endDate">Até</label>
                <input id="endDate" type="date" name="endDate" value="{{ $endDate }}">
            </div>
            <div class="actions" style="align-self:end;">
                <button type="submit" class="primary">Aplicar filtro</button>
                <a class="button ghost" href="{{ route('analytics.show', ['shortCode' => $shortCode]) }}">Limpar</a>
            </div>
        </form>
    </section>

    <div class="grid cards-2 section">
        <section class="table-panel">
            <div class="section-head">
                <div>
                    <h2>Últimas visitas</h2>
                    <p>Dados essenciais para decidir onde investir atenção.</p>
                </div>
            </div>

            @if ($items->isEmpty())
                <div class="empty">Nenhuma visita encontrada para este período.</div>
            @else
                <table class="table">
                    <thead>
                    <tr><th>Data</th><th>Origem</th><th>Local</th><th>Referer</th></tr>
                    </thead>
                    <tbody>
                    @foreach ($items->take(25) as $item)
                        <tr>
                            <td>
                                <div style="font-weight:700;">{{ data_get($item, 'dateVisited') ?? data_get($item, 'visitedAt') ?? data_get($item, 'date') ?? 'n/d' }}</div>
                                <div class="meta">{{ data_get($item, 'deviceType') ?? data_get($item, 'userAgent') ?? 'dispositivo n/d' }}</div>
                            </td>
                            <td>
                                <div style="font-weight:700;">{{ data_get($item, 'visitLocation.countryCode') ?? data_get($item, 'countryCode') ?? 'n/d' }}</div>
                                <div class="meta">{{ data_get($item, 'visitLocation.countryName') ?? data_get($item, 'countryName') ?? 'local n/d' }}</div>
                            </td>
                            <td>
                                <div style="font-weight:700;">{{ data_get($item, 'visitLocation.cityName') ?? data_get($item, 'cityName') ?? 'n/d' }}</div>
                                <div class="meta">{{ data_get($item, 'visitLocation.regionName') ?? data_get($item, 'regionName') ?? 'região n/d' }}</div>
                            </td>
                            <td style="word-break: break-word;">{{ data_get($item, 'referer') ?? data_get($item, 'refererUri') ?? data_get($item, 'refererUrl') ?? 'direto ou não informado' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </section>

        <aside class="card">
            <div class="section-head">
                <div>
                    <h2>Contexto da campanha</h2>
                    <p>Metadados usados na operação e na tomada de decisão.</p>
                </div>
            </div>
            <ul class="list">
                <li><span class="label">Destino</span><span class="value" style="max-width:65%; overflow-wrap:anywhere;">{{ $link->long_url }}</span></li>
                <li><span class="label">Status</span><span class="value">{{ $link->status }}</span></li>
                <li><span class="label">Tipo</span><span class="value">{{ $link->is_free_link ? 'Free' : 'Premium' }}</span></li>
                <li><span class="label">Expiração</span><span class="value">{{ $link->valid_until?->format('d/m/Y H:i') ?? 'Sem expiração' }}</span></li>
                <li><span class="label">UTMs</span><span class="value">{{ $utmCount ? $utmCount . ' configurada(s)' : 'Nenhuma' }}</span></li>
            </ul>
            @if ($tags->isNotEmpty())
                <div style="margin-top:16px; display:flex; gap:8px; flex-wrap:wrap;">
                    @foreach ($tags as $tag)
                        <span class="badge info">{{ $tag }}</span>
                    @endforeach
                </div>
            @endif
            @if ($countries->isNotEmpty())
                <div style="margin-top:20px;">
                    <h3 style="margin:0 0 8px;">Principais países</h3>
                    <ul class="list">
                        @foreach ($countries as $country => $count)
                            <li><span class="label">{{ $country }}</span><span class="value">{{ $count }} visita(s)</span></li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </aside>
    </div>
@endsection
