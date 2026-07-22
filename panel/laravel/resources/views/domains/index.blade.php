<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Domínios próprios</title>
</head>
<body>
    <main>
        <h1>Domínios próprios</h1>

        @if (session('status'))
            <p>{{ session('status') }}</p>
        @endif

        @if ($errors->any())
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

        <p>Para publicar links em um domínio próprio, aponte um registro CNAME (ou A) do seu domínio para <strong>{{ $dnsTarget }}</strong> e depois clique em Verificar.</p>

        <h2>Registrar novo domínio</h2>
        <form method="post" action="{{ route('domains.store') }}">
            @csrf
            <label>
                Domínio do cliente
                <input type="text" name="domain" value="{{ old('domain') }}" placeholder="links.cliente.com" maxlength="190" required>
            </label>
            <button type="submit">Registrar</button>
        </form>

        <h2>Meus domínios</h2>
        @if ($domains->isEmpty())
            <p>Nenhum domínio próprio cadastrado ainda.</p>
        @else
            <ul>
                @foreach ($domains as $item)
                    <li>
                        <strong>{{ $item->domain }}</strong> — status: {{ $item->status }}
                        @if ($item->dns_verified_at)
                            (verificado em {{ $item->dns_verified_at->format('d/m/Y H:i') }})
                        @endif
                        <form method="post" action="{{ route('domains.verify', $item) }}" style="display:inline">
                            @csrf
                            <button type="submit">Verificar DNS</button>
                        </form>
                        <form method="post" action="{{ route('domains.destroy', $item) }}" style="display:inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit">Remover</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        @endif
    </main>
</body>
</html>
