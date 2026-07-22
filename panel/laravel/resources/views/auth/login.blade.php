<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login — Painel Shlink</title>
    <style>
        :root { color-scheme: dark; }
        body { font-family: system-ui, -apple-system, sans-serif; background: #0f172a; color: #f1f5f9;
               display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
        .card { background: #1e293b; padding: 2rem; border-radius: 8px; width: 100%; max-width: 360px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        h1 { font-size: 1.25rem; margin: 0 0 1.5rem; }
        label { display: block; margin-bottom: 0.25rem; font-size: 0.875rem; }
        input[type=email], input[type=password] { width: 100%; padding: 0.5rem 0.75rem; border-radius: 4px;
            border: 1px solid #334155; background: #0f172a; color: #f1f5f9; box-sizing: border-box; margin-bottom: 1rem; }
        button { width: 100%; padding: 0.6rem; border-radius: 4px; border: 0; background: #2563eb;
                 color: white; font-weight: 600; cursor: pointer; }
        button:hover { background: #1d4ed8; }
        .errors { background: #7f1d1d; padding: 0.5rem 0.75rem; border-radius: 4px; margin-bottom: 1rem;
                  font-size: 0.875rem; }
        .errors ul { margin: 0; padding-left: 1rem; }
        .remember { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem; font-size: 0.875rem; }
    </style>
</head>
<body>
    <main class="card">
        <h1>Painel Shlink — Login</h1>

        @if ($errors->any())
            <div class="errors" role="alert">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('login.attempt') }}" novalidate>
            @csrf

            <label for="login">Usuário ou e-mail</label>
            <input id="login" name="login" type="text" required autofocus autocomplete="username" value="{{ old('login') }}">

            <label for="password">Senha</label>
            <input id="password" name="password" type="password" required autocomplete="current-password">

            <label class="remember">
                <input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                Manter conectado
            </label>

            <button type="submit">Entrar</button>
        </form>
    </main>
</body>
</html>
