<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Criar link</title>
</head>
<body>
    <main>
        <h1>Criar link</h1>
        <form method="post" action="{{ route('links.store') }}">
            @csrf
            <label>
                URL longa
                <input type="url" name="long_url" required>
            </label>
            <label>
                Premium
                <input type="checkbox" name="premium" value="1">
            </label>
            <label>
                Custom slug
                <input type="text" name="custom_slug">
            </label>
            <label>
                Domínio
                <input type="text" name="domain" placeholder="me.vr766.com">
            </label>
            <label>
                Expira em
                <input type="date" name="valid_until">
            </label>
            <button type="submit">Salvar</button>
        </form>
    </main>
</body>
</html>
