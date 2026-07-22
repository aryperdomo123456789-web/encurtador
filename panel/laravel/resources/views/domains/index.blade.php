<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Domínios</title>
</head>
<body>
    <main>
        <h1>Domínios</h1>
        @if (session('status'))
            <p>{{ session('status') }}</p>
        @endif
        <form method="post" action="{{ route('domains.store') }}">
            @csrf
            <label>
                Domínio do cliente
                <input type="text" name="domain" placeholder="links.cliente.com" required>
            </label>
            <button type="submit">Registrar</button>
        </form>
    </main>
</body>
</html>
