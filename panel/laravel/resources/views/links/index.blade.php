<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Links</title>
</head>
<body>
    <main>
        <h1>Links</h1>
        @if (session('status'))
            <p>{{ session('status') }}</p>
        @endif
        <a href="{{ route('links.create') }}">Criar link</a>
    </main>
</body>
</html>
