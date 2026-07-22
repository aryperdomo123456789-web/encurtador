<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Analytics {{ $shortCode }}</title>
</head>
<body>
    <main>
        <h1>Analytics: {{ $shortCode }}</h1>
        <pre>{{ json_encode($visits, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
    </main>
</body>
</html>
