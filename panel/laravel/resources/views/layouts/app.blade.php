<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Painel Shlink')</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: system-ui, -apple-system, sans-serif; margin: 0; color: #1f2937; background: #f9fafb; }
        header.top { background: #fff; border-bottom: 1px solid #e5e7eb; padding: .85rem 1.5rem; display: flex; align-items: center; justify-content: space-between; }
        header.top .brand { font-weight: 700; font-size: 1.05rem; color: #111827; text-decoration: none; }
        header.top nav a { color: #374151; text-decoration: none; margin-right: 1.1rem; font-size: .95rem; }
        header.top nav a.active { color: #2563eb; font-weight: 600; }
        header.top .user { display: flex; align-items: center; gap: .75rem; font-size: .9rem; color: #6b7280; }
        header.top .user form { margin: 0; }
        main.container { max-width: 1080px; margin: 2rem auto; padding: 0 1.25rem; }
        h1 { margin: 0 0 .25rem; font-size: 1.6rem; }
        h2 { margin-top: 2rem; font-size: 1.15rem; }
        .muted { color: #6b7280; }
        .card { border: 1px solid #e5e7eb; border-radius: 8px; padding: 1rem 1.25rem; background: #fff; margin: 1rem 0; }
        .grid { display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); }
        .stat { border: 1px solid #e5e7eb; background: #fff; border-radius: 8px; padding: 1rem 1.1rem; }
        .stat .label { font-size: .78rem; color: #6b7280; text-transform: uppercase; letter-spacing: .04em; }
        .stat .value { font-size: 1.7rem; font-weight: 700; margin-top: .35rem; color: #111827; }
        .stat .foot { font-size: .8rem; color: #6b7280; margin-top: .25rem; }
        table { width: 100%; border-collapse: collapse; font-size: .92rem; background: #fff; }
        th, td { padding: .65rem .8rem; border-bottom: 1px solid #f1f5f9; text-align: left; vertical-align: top; }
        th { background: #f9fafb; font-weight: 600; color: #374151; font-size: .82rem; text-transform: uppercase; letter-spacing: .03em; }
        tr:last-child td { border-bottom: none; }
        a { color: #2563eb; }
        a.btn, button.btn { display: inline-block; padding: .45rem .8rem; border-radius: 6px; border: 1px solid #d1d5db; background: #fff; color: #111827; font: inherit; cursor: pointer; text-decoration: none; font-size: .88rem; }
        a.btn.primary, button.btn.primary { background: #2563eb; color: #fff; border-color: #2563eb; }
        a.btn.danger, button.btn.danger { color: #991b1b; border-color: #fecaca; }
        .badge { display: inline-block; padding: .18rem .5rem; border-radius: 999px; font-size: .72rem; font-weight: 600; text-transform: uppercase; letter-spacing: .03em; }
        .badge-free { background: #e0e7ff; color: #3730a3; }
        .badge-premium { background: #fef3c7; color: #92400e; }
        .badge-active { background: #dcfce7; color: #166534; }
        .badge-expired { background: #fee2e2; color: #991b1b; }
        .alert { padding: .75rem 1rem; border-radius: 6px; margin: 1rem 0; }
        .alert-success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .empty { text-align: center; padding: 2.5rem 1rem; color: #6b7280; }
        code.short { font-family: ui-monospace, Menlo, monospace; background: #f3f4f6; padding: .12rem .4rem; border-radius: 4px; font-size: .85rem; }
        .actions { display: flex; gap: .35rem; flex-wrap: wrap; }
        form.inline { display: inline; margin: 0; }
        input[type="text"], input[type="url"], input[type="datetime-local"] { padding: .55rem .7rem; border: 1px solid #d1d5db; border-radius: 6px; width: 100%; font: inherit; }
        label { display: block; margin-top: .8rem; font-weight: 600; font-size: .9rem; color: #374151; }
        .field-hint { font-size: .8rem; color: #6b7280; margin-top: .2rem; }
        .toolbar { display: flex; gap: .5rem; align-items: center; justify-content: space-between; margin: 1rem 0 .5rem; flex-wrap: wrap; }
        .pagination { display: flex; gap: .35rem; margin-top: 1rem; }
        .pagination a, .pagination span { padding: .35rem .7rem; border: 1px solid #e5e7eb; border-radius: 6px; text-decoration: none; color: #374151; font-size: .85rem; }
        .pagination .current { background: #2563eb; color: #fff; border-color: #2563eb; }
    </style>
</head>
<body>
    <header class="top">
        <div style="display: flex; align-items: center; gap: 2rem;">
            <a href="{{ route('dashboard') }}" class="brand">Painel Shlink</a>
            <nav>
                <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">Dashboard</a>
                <a href="{{ route('links.index') }}" class="{{ request()->routeIs('links.*') ? 'active' : '' }}">Links</a>
                <a href="{{ route('domains.index') }}" class="{{ request()->routeIs('domains.*') ? 'active' : '' }}">Domínios</a>
            </nav>
        </div>
        <div class="user">
            @auth
                <span>{{ auth()->user()->email }}</span>
                <form method="post" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn">Sair</button>
                </form>
            @endauth
        </div>
    </header>

    <main class="container">
        @if (session('status'))
            <div class="alert alert-success" role="status">
                {{ session('status') }}
                @if (session('short_url'))
                    &middot; <a href="{{ session('short_url') }}" target="_blank" rel="noopener">Abrir link</a>
                @endif
            </div>
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

        @yield('content')
    </main>
</body>
</html>
