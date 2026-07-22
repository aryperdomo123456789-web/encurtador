<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Criar link</title>
</head>
<body>
    <main>
        <h1>Criar link (gratuito)</h1>

        @if ($errors->any())
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

        <p>Links gratuitos usam slug aleatório e expiram em 7 dias. Limite: 5 links por mês.</p>

        <form method="post" action="{{ route('links.store') }}">
            @csrf
            <label>
                URL longa
                <input
                    type="url"
                    name="long_url"
                    value="{{ old('long_url') }}"
                    maxlength="2048"
                    required>
            </label>
            <button type="submit">Encurtar</button>
        </form>

        <p><a href="{{ route('links.index') }}">Voltar</a></p>
    </main>
</body>
</html>
