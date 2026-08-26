@extends('layouts.app')

@section('title', 'MElink | Criar link')

@section('content')
    <div class="hero">
        <section class="hero-card">
            <span class="eyebrow">Fluxo gratuito</span>
            <h1 class="page-title">Criar link free</h1>
            <p class="page-subtitle">
                O backend decide o slug e a expiração de 7 dias. Você só informa o destino e o painel faz o
                restante sem quebrar o isolamento com o motor da plataforma.
            </p>

            <div class="hero-actions">
                <a class="button secondary" href="{{ route('links.index') }}">Voltar para links</a>
                @if ($isPremium)
                    <a class="button secondary" href="{{ route('links.premium') }}">Abrir fluxo premium</a>
                @endif
            </div>
        </section>

        <aside class="hero-side">
            <div class="card compact">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Regra free</h2>
                        <p class="meta">Sem custom slug e com cota mensal controlada.</p>
                    </div>
                    <span class="badge {{ $remainingFreeLinks > 0 ? 'info' : 'warning' }}">
                        {{ $remainingFreeLinks > 0 ? 'Há cota' : 'Sem cota' }}
                    </span>
                </div>

                <ul class="list">
                    <li>
                        <span class="label">Criados no mês</span>
                        <span class="value">{{ $createdThisMonth }}</span>
                    </li>
                    <li>
                        <span class="label">Restantes</span>
                        <span class="value">{{ $remainingFreeLinks }}</span>
                    </li>
                    <li>
                        <span class="label">Expiração padrão</span>
                        <span class="value">7 dias</span>
                    </li>
                </ul>
            </div>
        </aside>
    </div>

    <section class="card">
        <div class="section-head">
            <div>
                <h2>Destino do link</h2>
                <p>Informe apenas a URL final que o encurtador deve apontar.</p>
            </div>
        </div>

        <form method="post" action="{{ route('links.store') }}" class="form-grid">
            @csrf
            <div class="field">
                <label for="long_url">URL longa</label>
                <input
                    id="long_url"
                    type="url"
                    name="long_url"
                    value="{{ old('long_url') }}"
                    maxlength="2048"
                    placeholder="https://suaempresa.com/produto"
                    required>
                <div class="hint">Essa ação cria o link com slug aleatório e expiração automática.</div>
            </div>

            <div class="actions">
                <button type="submit" class="primary">Encurtar agora</button>
                <a class="button ghost" href="{{ route('links.index') }}">Cancelar</a>
            </div>
        </form>
    </section>
@endsection
