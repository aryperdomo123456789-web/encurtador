@extends('layouts.app')

@section('title', 'MElink | Analytics ' . $shortCode)

@section('content')
    @php
        $items = collect(data_get($visits, 'visits') ?? data_get($visits, 'data') ?? []);
        $pagination = data_get($visits, 'pagination', []);
        $totalItems = data_get($pagination, 'totalItems', $items->count());
        $page = data_get($pagination, 'currentPage', 1);
        $itemsPerPage = data_get($pagination, 'itemsPerPage', data_get($pagination, 'limit', $items->count()));
    @endphp

    <div class="hero">
        <section class="hero-card">
            <span class="eyebrow">Métricas</span>
            <h1 class="page-title">Analytics do slug</h1>
            <p class="page-subtitle">
                <code>{{ $shortCode }}</code> está retornando dados da plataforma. A visão abaixo prioriza leitura rápida
                para suporte e validação operacional.
            </p>

            <div class="hero-actions">
                <a class="button secondary" href="{{ route('links.index') }}">Voltar para links</a>
            </div>
        </section>

        <aside class="hero-side">
            <div class="card compact">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Resumo da consulta</h2>
                        <p class="meta">Resposta direta da API do motor.</p>
                    </div>
                    <span class="badge info">Read-only</span>
                </div>

                <div class="grid" style="gap: 10px;">
                    <div class="kpi"><strong>{{ $totalItems }}</strong><span>visitas encontradas</span></div>
                    <div class="kpi"><strong>{{ $page }}</strong><span>página atual</span></div>
                    <div class="kpi"><strong>{{ $itemsPerPage }}</strong><span>itens por página</span></div>
                </div>
            </div>
        </aside>
    </div>

    <div class="grid cards-2 section">
        <section class="table-panel">
            <div class="section-head">
                <div>
                    <h2>Últimas visitas</h2>
                    <p>Exibição segura dos campos mais úteis encontrados na resposta.</p>
                </div>
            </div>

            @if ($items->isEmpty())
                <div class="empty">Nenhuma visita encontrada para este short code no período consultado.</div>
            @else
                <table class="table">
                    <thead>
                    <tr>
                        <th>Data</th>
                        <th>Origem</th>
                        <th>Local</th>
                        <th>Referer</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($items->take(10) as $item)
                        <tr>
                            <td>
                                <div style="font-weight:700;">{{ data_get($item, 'dateVisited') ?? data_get($item, 'visitedAt') ?? data_get($item, 'date') ?? 'n/d' }}</div>
                                <div class="meta">{{ data_get($item, 'userAgent') ?? data_get($item, 'deviceType') ?? 'n/d' }}</div>
                            </td>
                            <td>
                                <div style="font-weight:700;">{{ data_get($item, 'visitLocation.countryCode') ?? data_get($item, 'countryCode') ?? 'n/d' }}</div>
                                <div class="meta">{{ data_get($item, 'visitLocation.countryName') ?? data_get($item, 'countryName') ?? 'n/d' }}</div>
                            </td>
                            <td>
                                <div style="font-weight:700;">{{ data_get($item, 'visitLocation.cityName') ?? data_get($item, 'cityName') ?? 'n/d' }}</div>
                                <div class="meta">{{ data_get($item, 'visitLocation.regionName') ?? data_get($item, 'regionName') ?? 'n/d' }}</div>
                            </td>
                            <td style="word-break: break-word;">
                                {{ data_get($item, 'referer') ?? data_get($item, 'refererUri') ?? data_get($item, 'refererUrl') ?? 'n/d' }}
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </section>

        <section class="card">
            <div class="section-head">
                <div>
                    <h2>Payload bruto</h2>
                    <p>Útil para depuração quando o schema da API muda ou quando faltam dados agregados.</p>
                </div>
            </div>
            <pre>{{ json_encode($visits, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </section>
    </div>
@endsection
