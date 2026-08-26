@extends('layouts.app')

@section('title', 'MElink | Tokens API')

@section('content')
    <div class="hero">
        <section class="hero-card">
            <span class="eyebrow">Integrações</span>
            <h1 class="page-title">Tokens da API</h1>
            <p class="page-subtitle">Conecte o MElink a sistemas, agências e automações sem compartilhar sua senha. Cada token tem scopes próprios e pode ser revogado a qualquer momento.</p>
        </section>
        <aside class="hero-side">
            <div class="card compact">
                <h2 class="card-title">API v1</h2>
                <p class="meta">Bearer tokens · JSON · scopes · expiração</p>
                <a class="button secondary" href="{{ url('/api/v1/me') }}" target="_blank" rel="noopener">Ver endpoint</a>
            </div>
        </aside>
    </div>

    @if (isset($newToken))
        <div class="alert success" role="alert">
            <strong>Token criado. Copie agora — ele não será mostrado novamente.</strong>
            <div style="display:flex; gap:10px; align-items:center; margin-top:12px; flex-wrap:wrap;">
                <code style="display:block; padding:12px; background:rgba(15,23,42,.08); border-radius:10px; word-break:break-all; flex:1; min-width:240px;">{{ $newToken }}</code>
                <button type="button" class="button secondary" onclick="navigator.clipboard?.writeText(@js($newToken))">Copiar token</button>
            </div>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert error" role="alert">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    @if (session('status'))
        <div class="alert success" role="status">{{ session('status') }}</div>
    @endif

    <section class="grid-two">
        <div class="card">
            <div class="card-header">
                <div>
                    <h2 class="card-title">Criar token</h2>
                    <p class="meta">Use um token por integração para revogar sem interromper as outras.</p>
                </div>
            </div>
            <form method="POST" action="{{ route('api-tokens.store') }}" class="stack">
                @csrf
                <div class="field">
                    <label for="name">Nome da integração</label>
                    <input id="name" name="name" required maxlength="80" value="{{ old('name') }}" placeholder="Ex.: Automação da agência">
                </div>
                <fieldset class="stack" style="border:0; padding:0; margin:0;">
                    <legend style="font-weight:700; margin-bottom:8px;">Permissões</legend>
                    <label class="remember"><input type="checkbox" name="scopes[]" value="read" checked> Ler links e conta</label>
                    <label class="remember"><input type="checkbox" name="scopes[]" value="write"> Criar e excluir links</label>
                    <label class="remember"><input type="checkbox" name="scopes[]" value="analytics"> Consultar analytics</label>
                </fieldset>
                <div class="field">
                    <label for="expires_in_days">Expiração</label>
                    <select id="expires_in_days" name="expires_in_days">
                        <option value="365">12 meses</option>
                        <option value="180">6 meses</option>
                        <option value="90">90 dias</option>
                        <option value="0">Sem expiração — somente quando necessário</option>
                    </select>
                </div>
                <button type="submit">Gerar token seguro</button>
            </form>
        </div>

        <div class="card">
            <div class="card-header">
                <div>
                    <h2 class="card-title">Tokens existentes</h2>
                    <p class="meta">O segredo nunca é armazenado em texto puro.</p>
                </div>
            </div>
            @forelse ($tokens as $token)
                <div style="display:flex; justify-content:space-between; gap:16px; padding:14px 0; border-bottom:1px solid rgba(15,23,42,.09); align-items:flex-start;">
                    <div>
                        <strong>{{ $token->name }}</strong>
                        <div class="meta">mlk_live_{{ $token->token_prefix }} · {{ implode(', ', (array) $token->scopes) }}</div>
                        <div class="meta">{{ $token->revoked_at ? 'Revogado' : ($token->expires_at ? 'Expira '.$token->expires_at->format('d/m/Y') : 'Sem expiração') }} · último uso {{ $token->last_used_at?->diffForHumans() ?? 'nunca' }}</div>
                    </div>
                    @if (! $token->revoked_at)
                        <form method="POST" action="{{ route('api-tokens.destroy', $token) }}" onsubmit="return confirm('Revogar este token? A integração perderá acesso imediatamente.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="button danger">Revogar</button>
                        </form>
                    @endif
                </div>
            @empty
                <div class="empty">Nenhum token criado. Gere um por integração e nunca compartilhe sua senha.</div>
            @endforelse
        </div>
    </section>
@endsection
