@extends('layouts.app')

@section('title', 'MElink | Auditoria')
@section('meta_description', 'Registros de criação, alteração e exclusão do painel.')

@section('content')
    <div class="hero">
        <section class="hero-card">
            <span class="eyebrow">Admin do dono</span>
            <h1 class="page-title">Auditoria</h1>
            <p class="page-subtitle">
                Acompanhe quem criou, alterou ou removeu dados no painel com data, hora, IP e request ID.
            </p>

            <div class="hero-actions">
                <a class="button secondary" href="{{ route('admin.dashboard') }}">Voltar ao admin</a>
                <a class="button secondary" href="{{ route('admin.rich-previews.index') }}">Rich previews</a>
            </div>
        </section>
    </div>

    <section class="card section">
        <form method="get" action="{{ route('admin.audit-logs.index') }}" class="form-grid" style="margin-bottom: 16px;">
            <div class="field">
                <label for="subject">Recurso</label>
                <input id="subject" type="search" name="subject" value="{{ $subject }}" placeholder="ex.: RichPreview, ShortLink">
            </div>
            <div class="field">
                <label for="action">Ação</label>
                <select id="action" name="action">
                    <option value="">Todas</option>
                    <option value="created" {{ $action === 'created' ? 'selected' : '' }}>created</option>
                    <option value="updated" {{ $action === 'updated' ? 'selected' : '' }}>updated</option>
                    <option value="deleted" {{ $action === 'deleted' ? 'selected' : '' }}>deleted</option>
                </select>
            </div>
            <div class="actions">
                <button type="submit" class="primary">Filtrar</button>
                @if($action !== '' || $subject !== '')
                    <a class="button ghost" href="{{ route('admin.audit-logs.index') }}">Limpar</a>
                @endif
            </div>
        </form>

        @if ($logs->isEmpty())
            <div class="mini-card">Nenhum evento de auditoria encontrado.</div>
        @else
            <div class="table-panel" style="margin-top: 14px;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Ação</th>
                            <th>Recurso</th>
                            <th>Registro</th>
                            <th>Usuário</th>
                            <th>Data/Hora</th>
                            <th>Contexto</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($logs as $log)
                            <tr>
                                <td>#{{ $log->id }}</td>
                                <td><span class="badge info">{{ $log->action }}</span></td>
                                <td>{{ class_basename($log->subject_type) }}</td>
                                <td>
                                    <div style="font-weight:700;">ID {{ $log->subject_id ?? 'n/d' }}</div>
                                    <div class="meta">{{ $log->subject_type }}</div>
                                </td>
                                <td>
                                    <div style="font-weight:700;">{{ $log->actor?->name ?? 'Sistema' }}</div>
                                    <div class="meta">user_id: {{ $log->actor_user_id ?? 'n/d' }}</div>
                                </td>
                                <td>
                                    <div style="font-weight:700;">{{ $log->created_at?->format('d/m/Y H:i:s') ?? 'n/d' }}</div>
                                    <div class="meta">request: {{ $log->request_id ?? 'n/d' }}</div>
                                </td>
                                <td class="meta" style="max-width:320px; word-break:break-word;">
                                    <div>IP: {{ $log->ip_address ?? 'n/d' }}</div>
                                    <div>Route: {{ $log->metadata['route'] ?? 'n/d' }}</div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
