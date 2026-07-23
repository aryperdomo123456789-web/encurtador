@extends('layouts.app')

@section('title', 'Meus links')

@section('content')
<div class="page">
    <div class="page-header">
        <h1>Meus links</h1>
        <a href="{{ route('links.create') }}" class="btn btn-primary">+ Novo link</a>
    </div>

    <form method="GET" class="filters">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Buscar por slug ou URL..." class="input">
        <select name="filter" class="input">
            <option value="">Todos</option>
            <option value="free" @selected(request('filter')==='free')>Free</option>
            <option value="premium" @selected(request('filter')==='premium')>Premium</option>
            <option value="expiring" @selected(request('filter')==='expiring')>Expirando</option>
        </select>
        <button type="submit" class="btn btn-secondary">Filtrar</button>
    </form>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card">
        @if($links->isEmpty())
            <p class="empty">Nenhum link encontrado.</p>
        @else
            <table class="table">
                <thead>
                    <tr>
                        <th>Slug</th>
                        <th>Destino</th>
                        <th>Tipo</th>
                        <th>Válido até</th>
                        <th>Criado</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($links as $link)
                    <tr>
                        <td>
                            <a href="{{ $link->shlink_short_url }}" target="_blank" rel="noopener">
                                <code>{{ $link->shlink_short_code }}</code>
                            </a>
                        </td>
                        <td class="truncate" title="{{ $link->long_url }}">{{ $link->long_url }}</td>
                        <td>
                            @if($link->is_free_link)
                                <span class="badge badge-neutral">Free</span>
                            @else
                                <span class="badge badge-primary">Premium</span>
                            @endif
                        </td>
                        <td>
                            @if($link->valid_until)
                                {{ $link->valid_until->format('d/m/Y') }}
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>
                        <td>{{ $link->created_at->diffForHumans() }}</td>
                        <td class="actions">
                            <a href="{{ route('analytics.show', $link->shlink_short_code) }}" class="link-muted">Analytics</a>
                            <form method="POST" action="{{ route('links.destroy', $link) }}" onsubmit="return confirm('Excluir este link?')" style="display:inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-link-danger">Excluir</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>

            <div class="pagination-wrap">{{ $links->links() }}</div>
        @endif
    </div>
</div>
@endsection
