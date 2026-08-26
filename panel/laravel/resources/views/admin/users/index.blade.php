@extends('layouts.app')

@section('title', 'MElink | Admin de usuários')

@section('content')
    <div class="hero">
        <section class="hero-card">
            <span class="eyebrow">Admin do dono</span>
            <h1 class="page-title">Usuários do painel</h1>
            <p class="page-subtitle">
                Visão consolidada das contas, com busca rápida, contagem operacional e acesso aos detalhes de cada usuário.
            </p>

            <div class="hero-actions">
                <a class="button secondary" href="{{ route('dashboard') }}">Voltar ao dashboard</a>
                <a class="button secondary" href="{{ route('admin.users.index') }}">Atualizar lista</a>
                <a class="button secondary" href="{{ route('admin.branding.edit') }}">Editar marca</a>
                <a class="button secondary" href="{{ route('admin.rich-previews.index') }}">Rich Preview</a>
            </div>
        </section>

        <aside class="hero-side">
            <div class="card compact">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Resumo</h2>
                        <p class="meta">Indicadores rápidos da base de contas.</p>
                    </div>
                    <span class="badge info">Owner only</span>
                </div>

                <ul class="list">
                    <li><span class="label">Total de usuários</span><span class="value">{{ $summary['total'] }}</span></li>
                    <li><span class="label">Contas comuns</span><span class="value">{{ $summary['common'] }}</span></li>
                    <li><span class="label">Owners</span><span class="value">{{ $summary['owners'] }}</span></li>
                    <li><span class="label">Assinaturas premium</span><span class="value">{{ $summary['premium'] }}</span></li>
                    <li><span class="label">Links registrados</span><span class="value">{{ $summary['links'] }}</span></li>
                    <li><span class="label">Domínios cadastrados</span><span class="value">{{ $summary['domains'] }}</span></li>
                </ul>
            </div>
        </aside>
    </div>

    <section class="card section">
        <form method="get" action="{{ route('admin.users.index') }}" class="form-grid">
            <div class="field">
                <label for="search">Buscar</label>
                <input id="search" type="search" name="search" value="{{ $search }}" placeholder="nome ou e-mail">
            </div>
            <div class="actions">
                <button type="submit" class="primary">Filtrar</button>
                @if($search !== '')
                    <a class="button ghost" href="{{ route('admin.users.index') }}">Limpar</a>
                @endif
            </div>
        </form>
    </section>

    <section class="table-panel section">
        <div class="section-head">
            <div>
                <h2>Contas</h2>
                <p>Abra um usuário para ver o histórico e, se necessário, redefinir o acesso.</p>
            </div>
        </div>

        <table class="table">
            <thead>
                <tr>
                    <th>Usuário</th>
                    <th>Perfil</th>
                    <th>Uso</th>
                    <th>Criado em</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $item)
                    <tr>
                        <td>
                            <div style="font-weight:700;">{{ $item->name }}</div>
                            <div class="meta">{{ $item->email }}</div>
                        </td>
                        <td>
                            <span class="badge {{ $item->role === 'owner' ? 'info' : 'muted' }}">
                                {{ $item->role }}
                            </span>
                            <div class="meta" style="margin-top:8px;">
                                {{ $item->isPremium() ? 'premium' : 'free' }}
                            </div>
                        </td>
                        <td>
                            <div class="meta">Links: <strong>{{ $item->short_links_count }}</strong></div>
                            <div class="meta">Domínios: <strong>{{ $item->customer_domains_count }}</strong></div>
                            <div class="meta">Assinaturas: <strong>{{ $item->subscriptions_count }}</strong></div>
                        </td>
                        <td>
                            <div style="font-weight:700;">{{ $item->created_at?->format('d/m/Y H:i') ?? 'n/d' }}</div>
                        </td>
                        <td>
                            <div class="actions" style="justify-content:flex-end;">
                                <a class="button secondary" href="{{ route('admin.users.show', $item) }}">Abrir</a>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div style="margin-top: 18px;">
            {{ $users->links() }}
        </div>
    </section>
@endsection
