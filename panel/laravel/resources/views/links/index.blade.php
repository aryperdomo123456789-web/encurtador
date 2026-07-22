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
            <p role="status">{{ session('status') }}</p>
        @endif

        @if (session('short_url'))
            <p>
                Link curto:
                <a href="{{ session('short_url') }}" target="_blank" rel="noopener">
                    {{ session('short_url') }}
                </a>
            </p>
        @endif

        <p><a href="{{ route('links.create') }}">Criar link</a></p>
    </main>
</body>
</html>
