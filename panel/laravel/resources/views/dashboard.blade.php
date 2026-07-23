@extends('layouts.app')

@section('title', 'Painel')

@section('content')
<div class="page">
    <div class="page-header">
        <h1>Painel</h1>
        <a href="{{ route('links.create') }}" class="btn btn-primary">+ Novo link</a>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Total de links</div>
            <div class="stat-value">{{ $totalLinks }}</div>
            <div class="stat-sub">{{ $freeLinks }} free · {{ $premiumLinks }} premium</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Cliques totais</div>
            <div class="stat-value">{{ $totalClicks }}</div>
            <div class="stat-sub">Todos os links</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Cota free (mês)</div>
            <div class="stat-value">{{ $quotaUsed }} / {{ $quotaLimit }}</div>
            <div class="stat-sub">Reset no dia 1</div>
        </div>
        <div class="stat-card {{ $expiringSoon > 0 ? 'stat-card--warn' : '' }}">
            <div class="stat-label">Expirando em 3 dias</div>
            <div class="stat-value">{{ $expiringSoon }}</div>
            <div class="stat-sub">Free links próximos do fim</div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2>Links recentes</h2>
            <a href="{{ route('links.index') }}" class="link-muted">Ver todos →</a>
        </div>
        @if($recentLinks->isEmpty())
            <p class="empty">Nenhum link ainda. <a href="{{ route('links.create') }}">Crie o primeiro</a>.</p>
        @else
            <table class="table">
                <thead>
                    <tr><th>Slug</th><th>Destino</th><th>Tipo</th><th>Criado</th><th></th></tr>
                </thead>
                <tbody>
                @foreach($recentLinks as $link)
                    <tr>
                        <td><code>{{ $link->shlink_short_code }}</code></td>
                        <td class="truncate">{{ $link->long_url }}</td>
                        <td>
                            @if($link->is_free_link)
                                <span class="badge badge-neutral">Free</span>
                            @else
                                <span class="badge badge-primary">Premium</span>
                            @endif
                        </td>
                        <td>{{ $link->created_at->diffForHumans() }}</td>
                        <td><a href="{{ route('analytics.show', $link->shlink_short_code) }}" class="link-muted">Analytics</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection
