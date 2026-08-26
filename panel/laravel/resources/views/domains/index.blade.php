@extends('layouts.app')

@section('title', 'MElink | Domínios')

@section('content')
    <div class="hero">
        <section class="hero-card">
            <span class="eyebrow">Domínio próprio</span>
            <h1 class="page-title">Gerenciar domínios</h1>
            <p class="page-subtitle">
                Cadastre o domínio do cliente, confirme o DNS e acompanhe o status do TLS sem sair do painel.
                O proxy da borda continua responsável pela emissão do certificado.
            </p>

            <div class="hero-actions">
                <a class="button secondary" href="#registrar">Registrar domínio</a>
                <a class="button secondary" href="#lista-dominios">Ver lista</a>
            </div>
        </section>

        <aside class="hero-side">
            <div class="card compact">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Destino de DNS</h2>
                        <p class="meta">Aponte os clientes para o host abaixo.</p>
                    </div>
                    <span class="badge info">CNAME/A</span>
                </div>

                <div class="empty" style="margin: 0; color: var(--text);">
                    <div class="meta" style="margin-bottom: 8px;">Alvo esperado</div>
                    <div style="font-size: 1.2rem; font-weight: 800; letter-spacing: -0.03em;">
                        {{ $dnsTarget }}
                    </div>
                </div>
            </div>
        </aside>
    </div>

    <div class="grid cards-3 section">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Passo 1</h2>
                <span class="badge info">DNS</span>
            </div>
            <p class="meta">Crie um CNAME ou A apontando para o host acima. A verificação precisa bater exatamente.</p>
        </div>
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Passo 2</h2>
                <span class="badge warning">Registro</span>
            </div>
            <p class="meta">Cadastre o domínio aqui no painel para que ele possa ser validado e enviado ao motor da plataforma.</p>
        </div>
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Passo 3</h2>
                <span class="badge success">TLS</span>
            </div>
            <p class="meta">Depois da ativação, a borda passa a emitir e renovar o certificado automaticamente.</p>
        </div>
    </div>

    <section class="card section" id="registrar">
        <div class="section-head">
            <div>
                <h2>Registrar domínio</h2>
                <p>Use um domínio ou subdomínio que já esteja apontando para o host correto.</p>
            </div>
        </div>

        <form method="post" action="{{ route('domains.store') }}" class="form-grid">
            @csrf
            <div class="field">
                <label for="domain-input">Domínio ou subdomínio</label>
                <input id="domain-input" type="text" name="domain" value="{{ old('domain') }}" placeholder="links.suaempresa.com" maxlength="190" required>
                <div class="hint">Domínio inválido, reservado ou já usado por outra conta será rejeitado pelo backend.</div>
            </div>
            <div class="actions">
                <button type="submit" class="primary">Registrar domínio</button>
            </div>
        </form>
    </section>

    <section class="table-panel section" id="lista-dominios">
        <div class="section-head">
            <div>
                <h2>Domínios da conta</h2>
                <p>Status de DNS, TLS e ações disponíveis para cada domínio.</p>
            </div>
        </div>

        @if ($domains->isEmpty())
            <div class="empty">Nenhum domínio foi cadastrado ainda.</div>
        @else
            <table class="table">
                <thead>
                <tr>
                    <th>Domínio</th>
                    <th>DNS</th>
                    <th>TLS</th>
                    <th>Atualização</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach ($domains as $item)
                    @php
                        $statusBadge = match ($item->status) {
                            'active' => 'success',
                            'pending_dns' => 'warning',
                            default => 'danger',
                        };
                    @endphp
                    <tr>
                        <td>
                            <div style="font-weight:700;">{{ $item->domain }}</div>
                            <div class="meta">{{ $item->is_primary ? 'domínio principal' : 'domínio secundário' }}</div>
                        </td>
                        <td>
                            <span class="badge {{ $statusBadge }}">
                                {{ $item->status === 'active' ? 'ativo' : ($item->status === 'pending_dns' ? 'pendente' : 'erro') }}
                            </span>
                            <div class="meta" style="margin-top:8px;">
                                {{ $item->dns_target ?? $dnsTarget }}
                            </div>
                        </td>
                        <td>
                            <span class="badge {{ $item->tls_status === 'active' ? 'success' : ($item->tls_status === 'error' ? 'danger' : 'warning') }}">
                                {{ $item->tls_status }}
                            </span>
                            @if ($item->tls_last_error)
                                <div class="meta" style="margin-top:8px;">{{ $item->tls_last_error }}</div>
                            @endif
                        </td>
                        <td>
                            <div style="font-weight:700;">{{ $item->dns_verified_at?->format('d/m/Y H:i') ?? 'n/d' }}</div>
                            <div class="meta">{{ $item->shlink_domain_registered_at?->format('d/m/Y H:i') ?? 'não registrado na plataforma' }}</div>
                        </td>
                        <td>
                            <div class="actions" style="justify-content:flex-end;">
                                @if ($item->status !== 'active')
                                    <form method="post" action="{{ route('domains.verify', $item) }}">
                                        @csrf
                                        <button type="submit" class="button primary">Verificar DNS</button>
                                    </form>
                                @endif
                                <form method="post" action="{{ route('domains.tls', $item) }}">
                                    @csrf
                                    <button type="submit" class="button ghost">Testar HTTPS</button>
                                </form>
                                <form method="post" action="{{ route('domains.destroy', $item) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="button danger" onclick="return confirm('Remover {{ $item->domain }} do painel?');">Remover</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </section>
@endsection
