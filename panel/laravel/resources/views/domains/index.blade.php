<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Domínios próprios</title>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; max-width: 860px; margin: 2rem auto; padding: 0 1rem; color: #1f2937; }
        h1 { margin-bottom: 0.25rem; }
        h2 { margin-top: 2rem; }
        .muted { color: #6b7280; }
        .card { border: 1px solid #e5e7eb; border-radius: 8px; padding: 1rem 1.25rem; margin: 1rem 0; background: #fff; }
        .dns-target { display: inline-block; padding: .35rem .6rem; background: #f3f4f6; border: 1px solid #d1d5db; border-radius: 6px; font-family: ui-monospace, Menlo, monospace; font-weight: 600; }
        .badge { display: inline-block; padding: .2rem .55rem; border-radius: 999px; font-size: .8rem; font-weight: 600; text-transform: uppercase; letter-spacing: .02em; margin-left: .5rem; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-active  { background: #dcfce7; color: #166534; }
        .badge-error   { background: #fee2e2; color: #991b1b; }
        .badge-tls-active  { background: #dbeafe; color: #1e40af; }
        .badge-tls-pending { background: #ede9fe; color: #5b21b6; }
        .badge-tls-error   { background: #fee2e2; color: #991b1b; }
        .alert { padding: .75rem 1rem; border-radius: 6px; margin: 1rem 0; }
        .alert-success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-error   { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        form.inline { display: inline; margin-right: .35rem; }
        button { cursor: pointer; padding: .4rem .8rem; border-radius: 6px; border: 1px solid #d1d5db; background: #fff; font: inherit; }
        button.primary { background: #2563eb; color: #fff; border-color: #2563eb; }
        button.secondary { background: #f3f4f6; color: #1f2937; }
        button.danger  { background: #fff; color: #991b1b; border-color: #fecaca; }
        input[type="text"] { padding: .5rem .6rem; border: 1px solid #d1d5db; border-radius: 6px; width: 100%; max-width: 360px; }
        .domain-row { display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap; }
        .domain-meta { font-size: .85rem; color: #6b7280; margin-top: .35rem; }
        .tls-error { font-size: .8rem; color: #991b1b; margin-top: .25rem; font-family: ui-monospace, Menlo, monospace; }
    </style>
</head>
<body>
    <main>
        <h1>Domínios próprios</h1>
        <p class="muted">Publique seus links em um domínio da sua marca.</p>

        @if (session('status'))
            <div class="alert alert-success" role="status">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-error" role="alert">
                <strong>Não foi possível concluir:</strong>
                <ul style="margin: .3rem 0 0 1rem;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card">
            <h2 style="margin-top:0">1. Aponte o DNS</h2>
            <p>Crie um registro <strong>CNAME</strong> (ou <strong>A</strong>) do seu domínio para:</p>
            <p><span class="dns-target">{{ $dnsTarget }}</span></p>
            <p class="muted">A verificação usa esse valor exato. Se o seu provedor não aceita CNAME na raiz, use um subdomínio (ex.: <code>links.suaempresa.com</code>).</p>
        </div>

        <div class="card">
            <h2 style="margin-top:0">2. Registrar domínio</h2>
            <form method="post" action="{{ route('domains.store') }}">
                @csrf
                <label for="domain-input">Domínio ou subdomínio</label><br>
                <input id="domain-input" type="text" name="domain" value="{{ old('domain') }}"
                       placeholder="links.suaempresa.com" maxlength="190" required>
                <button type="submit" class="primary">Registrar</button>
            </form>
        </div>

        <h2>Meus domínios</h2>
        @if ($domains->isEmpty())
            <p class="muted">Nenhum domínio próprio cadastrado ainda.</p>
        @else
            @foreach ($domains as $item)
                @php
                    $status = $item->status;
                    $badgeClass = match ($status) {
                        'active'      => 'badge-active',
                        'pending_dns' => 'badge-pending',
                        default       => 'badge-error',
                    };
                    $badgeLabel = match ($status) {
                        'active'      => 'Ativo',
                        'pending_dns' => 'Pendente DNS',
                        default       => 'Erro de verificação',
                    };
                    $tlsStatus = $item->tls_status ?? 'pending';
                    $tlsClass = match ($tlsStatus) {
                        'active' => 'badge-tls-active',
                        'error'  => 'badge-tls-error',
                        default  => 'badge-tls-pending',
                    };
                    $tlsLabel = match ($tlsStatus) {
                        'active' => 'HTTPS ativo',
                        'error'  => 'HTTPS com erro',
                        default  => 'HTTPS pendente',
                    };
                @endphp
                <div class="card">
                    <div class="domain-row">
                        <div>
                            <strong style="font-size:1.05rem">{{ $item->domain }}</strong>
                            <span class="badge {{ $badgeClass }}">{{ $badgeLabel }}</span>
                            @if ($status === 'active')
                                <span class="badge {{ $tlsClass }}">{{ $tlsLabel }}</span>
                            @endif
                            <div class="domain-meta">
                                @if ($item->dns_verified_at)
                                    DNS verificado em {{ $item->dns_verified_at->format('d/m/Y H:i') }}
                                @else
                                    Aguardando verificação de DNS
                                @endif
                                @if ($item->dns_target)
                                    · alvo: <code>{{ $item->dns_target }}</code>
                                @endif
                            </div>
                            @if ($tlsStatus === 'error' && !empty($item->tls_last_error))
                                <div class="tls-error">TLS: {{ \Illuminate\Support\Str::limit($item->tls_last_error, 160) }}</div>
                            @endif
                        </div>
                        <div>
                            @if ($status !== 'active')
                                <form method="post" action="{{ route('domains.verify', $item) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="primary">Verificar DNS</button>
                                </form>
                            @else
                                <form method="post" action="{{ route('domains.tls', $item) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="secondary">Testar HTTPS</button>
                                </form>
                            @endif
                            <form method="post" action="{{ route('domains.destroy', $item) }}" class="inline"
                                  onsubmit="return confirm('Remover {{ $item->domain }} do painel?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="danger">Remover</button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    </main>
</body>
</html>
