@extends('layouts.app')

@section('title', 'MElink | Links')

@section('content')
    <div class="hero">
        <section class="hero-card">
            <span class="eyebrow">Gestão de links</span>
            <h1 class="page-title">Links da conta</h1>
            <p class="page-subtitle">
                Aqui você acompanha o histórico de encurtamento, o limite mensal free e os atalhos para criar
                novos links sem alterar o motor da plataforma.
            </p>

            <div class="hero-actions">
                <a class="button primary" href="{{ route('links.create') }}">Criar link gratuito</a>
                @if ($isPremium)
                    <a class="button secondary" href="{{ route('links.premium') }}">Criar link premium</a>
                @endif
            </div>
        </section>

        <aside class="hero-side">
            <div class="card compact">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Resumo da cota</h2>
                        <p class="meta">Baseado no mês corrente em UTC.</p>
                    </div>
                    <span class="badge {{ $remainingFreeLinks > 0 ? 'info' : 'warning' }}">
                        {{ $remainingFreeLinks > 0 ? 'Livre' : 'No limite' }}
                    </span>
                </div>

                <ul class="list">
                    <li>
                        <span class="label">Criados no mês</span>
                        <span class="value">{{ $createdThisMonth }}</span>
                    </li>
                    <li>
                        <span class="label">Limite mensal</span>
                        <span class="value">{{ $freeLimit }}</span>
                    </li>
                    <li>
                        <span class="label">Restantes</span>
                        <span class="value">{{ max(0, $freeLimit - $createdThisMonth) }}</span>
                    </li>
                </ul>
            </div>
        </aside>
    </div>

    @if (session('short_url'))
        <div class="alert success" role="status">
            <strong>Link criado com sucesso.</strong>
            <div style="margin-top: 8px;">
                <a href="{{ session('short_url') }}" target="_blank" rel="noopener">{{ session('short_url') }}</a>
            </div>
        </div>
    @endif

    <section class="table-panel">
        <div class="section-head">
            <div>
                <h2>Histórico de links</h2>
                <p>Lista dos últimos encurtamentos criados para esta conta.</p>
            </div>
        </div>

        @if ($links->isEmpty())
            <div class="empty">
                Nenhum link foi criado ainda. O primeiro passo normalmente é um link gratuito para validar o fluxo.
            </div>
        @else
            <table class="table">
                <thead>
                <tr>
                    <th>Destino</th>
                    <th>Origem</th>
                    <th>Expira</th>
                    <th>Status</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach ($links as $link)
                    <tr>
                        <td>
                            <div style="font-weight:700; word-break: break-word;">{{ $link->long_url }}</div>
                            <div class="meta">
                                {{ $link->is_free_link ? 'free' : 'premium' }}
                                @if ($link->is_custom_slug)
                                    · custom slug
                                @endif
                            </div>
                        </td>
                        <td>
                            <div style="font-weight:700;">{{ $link->shlink_short_url ?? 'Pendente' }}</div>
                            <div class="meta">{{ $link->domain }}</div>
                        </td>
                        <td>
                            <div style="font-weight:700;">{{ $link->valid_until?->format('d/m/Y H:i') ?? 'Sem expiração' }}</div>
                            <div class="meta">{{ $link->generated_slug ?? $link->custom_slug ?? 'slug aleatório' }}</div>
                        </td>
                        <td>
                            <span class="badge {{ $link->status === 'active' ? 'success' : ($link->status === 'failed' ? 'danger' : 'warning') }}">
                                {{ $link->status }}
                            </span>
                        </td>
                        <td>
                            @if ($link->shlink_short_url)
                                <a class="button ghost" href="{{ $link->shlink_short_url }}" target="_blank" rel="noopener">Abrir</a>
                            @endif
                            <form method="POST" action="{{ route('links.destroy', $link) }}" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="button danger" onclick="return confirm('Remover este link?');">Excluir</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </section>
@endsection
