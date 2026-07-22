<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Criar link premium</title>
</head>
<body>
    <main>
        <h1>Criar link premium (customSlug)</h1>

        @if ($errors->any())
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

        <p>Links premium usam um slug escolhido por você. Você pode opcionalmente definir uma expiração de até 1 ano.</p>

        <form method="post" action="{{ route('links.premium.store') }}">
            @csrf
            <label>
                URL longa
                <input type="url" name="long_url" value="{{ old('long_url') }}" maxlength="2048" required>
            </label>
            <label>
                Slug personalizado
                <input
                    type="text"
                    name="custom_slug"
                    value="{{ old('custom_slug') }}"
                    minlength="3"
                    maxlength="40"
                    pattern="[a-z0-9][a-z0-9-]{1,38}[a-z0-9]"
                    required>
            </label>
            <label>
                Expira em (opcional)
                <input type="datetime-local" name="valid_until" value="{{ old('valid_until') }}">
            </label>
            <button type="submit">Encurtar</button>
        </form>

        <p><a href="{{ route('links.index') }}">Voltar</a></p>
    </main>
</body>
</html>
