@extends('layouts.app')

@section('title', 'Analytics — ' . $link->shlink_short_code)

@section('content')
<div class="page">
    <div class="page-header">
        <div>
            <h1>Analytics</h1>
            <p class="muted">
                <code>{{ $link->shlink_short_code }}</code> →
                <span class="truncate-inline">{{ $link->long_url }}</span>
            </p>
        </div>
        <a href="{{ route('links.index') }}" class="btn btn-secondary">← Voltar</a>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Total de visitas</div>
            <div class="stat-value">{{ $summary['total'] ?? 0 }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Últimos 7 dias</div>
            <div class="stat-value">{{ $summary['last7'] ?? 0 }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Hoje</div>
            <div class="stat-value">{{ $summary['today'] ?? 0 }}</div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h2>Cliques por dia</h2></div>
        <canvas id="clicksChart" height="90"></canvas>
    </div>

    <div class="analytics-grid">
        <div class="card">
            <div class="card-header"><h2>Top países</h2></div>
            @include('analytics.partials.top-list', ['items' => $topCountries ?? []])
        </div>
        <div class="card">
            <div class="card-header"><h2>Top referers</h2></div>
            @include('analytics.partials.top-list', ['items' => $topReferers ?? []])
        </div>
        <div class="card">
            <div class="card-header"><h2>Top navegadores</h2></div>
            @include('analytics.partials.top-list', ['items' => $topBrowsers ?? []])
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    const ctx = document.getElementById('clicksChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: @json(array_keys($clicksByDay ?? [])),
                datasets: [{
                    label: 'Cliques',
                    data: @json(array_values($clicksByDay ?? [])),
                    borderColor: '#4f46e5',
                    backgroundColor: 'rgba(79, 70, 229, 0.12)',
                    fill: true,
                    tension: 0.3,
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });
    }
</script>
@endsection
