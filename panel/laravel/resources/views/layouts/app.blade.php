<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'MElink'))</title>
    <meta name="description" content="@yield('meta_description', 'Painel operacional para gestão de links, domínios e cobrança.')">
    <meta property="og:title" content="@yield('title', config('app.name', 'MElink'))">
    <meta property="og:description" content="@yield('meta_description', 'Painel operacional para gestão de links, domínios e cobrança.')">
    <meta property="og:image" content="{{ $branding->socialImageUrl() }}">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:image" content="{{ $branding->socialImageUrl() }}">
    <link rel="icon" type="image/png" href="{{ $branding->faviconUrl() }}">
    <link rel="apple-touch-icon" href="{{ $branding->faviconUrl() }}">
    <meta name="theme-color" content="#2447f5">
    <style>
        :root {
            color-scheme: light;
            --bg: #f4f7fb;
            --bg-accent: #e8eefb;
            --surface: rgba(255, 255, 255, 0.88);
            --surface-strong: #ffffff;
            --surface-muted: #f7f9fc;
            --text: #101828;
            --muted: #667085;
            --border: rgba(16, 24, 40, 0.09);
            --border-strong: rgba(16, 24, 40, 0.14);
            --primary: #2447f5;
            --primary-strong: #1732b3;
            --primary-soft: rgba(36, 71, 245, 0.1);
            --success: #0f9d58;
            --warning: #c97a16;
            --danger: #d92d20;
            --shadow: 0 24px 80px rgba(15, 23, 42, 0.12);
            --shadow-soft: 0 12px 30px rgba(15, 23, 42, 0.08);
            --radius-xl: 28px;
            --radius-lg: 20px;
            --radius-md: 14px;
        }

        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Manrope", "Avenir Next", "Segoe UI", sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at top left, rgba(36, 71, 245, 0.12), transparent 30%),
                radial-gradient(circle at top right, rgba(15, 157, 88, 0.12), transparent 26%),
                linear-gradient(180deg, #ffffff 0%, var(--bg) 44%, #edf2ff 100%);
        }

        body::before {
            content: "";
            position: fixed;
            inset: 0;
            pointer-events: none;
            background-image:
                linear-gradient(rgba(16, 24, 40, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(16, 24, 40, 0.03) 1px, transparent 1px);
            background-size: 48px 48px;
            mask-image: linear-gradient(180deg, rgba(0, 0, 0, 0.65), transparent 92%);
            opacity: 0.45;
        }

        a { color: inherit; text-decoration: none; }
        a:hover { text-decoration: none; }
        .app-shell {
            position: relative;
            z-index: 1;
            width: min(1200px, calc(100% - 32px));
            margin: 0 auto;
            padding: 24px 0 56px;
        }
        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 18px 22px;
            border: 1px solid var(--border);
            border-radius: var(--radius-xl);
            background: rgba(255, 255, 255, 0.78);
            backdrop-filter: blur(18px);
            box-shadow: var(--shadow-soft);
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .brand-mark {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            object-fit: cover;
            background: #ffffff;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.32), 0 12px 24px rgba(36, 71, 245, 0.18);
        }
        .brand-title {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }
        .brand-title strong {
            font-size: 0.98rem;
            line-height: 1.2;
            letter-spacing: -0.02em;
        }
        .brand-title span,
        .muted {
            color: var(--muted);
        }
        .brand-title span {
            font-size: 0.86rem;
        }
        .nav {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }
        .nav-link,
        .nav-pill,
        .button,
        button,
        input[type="submit"] {
            appearance: none;
            border: 0;
            border-radius: 999px;
            font: inherit;
            font-weight: 700;
            letter-spacing: -0.01em;
            cursor: pointer;
            transition: transform 160ms ease, background 160ms ease, color 160ms ease, border-color 160ms ease, box-shadow 160ms ease;
        }
        .nav-link {
            padding: 10px 14px;
            border: 1px solid transparent;
            color: var(--muted);
            background: transparent;
        }
        .nav-link:hover,
        .nav-link.active {
            color: var(--primary-strong);
            background: var(--primary-soft);
            border-color: rgba(36, 71, 245, 0.14);
        }
        .nav-pill {
            padding: 10px 14px;
            color: #ffffff;
            background: linear-gradient(135deg, var(--primary) 0%, #3b66ff 100%);
            box-shadow: 0 12px 24px rgba(36, 71, 245, 0.18);
        }
        .nav-pill:hover,
        .button.primary:hover,
        button.primary:hover {
            transform: translateY(-1px);
            background: linear-gradient(135deg, var(--primary-strong) 0%, #2144f0 100%);
        }
        .page {
            padding-top: 28px;
        }
        .hero {
            display: grid;
            grid-template-columns: minmax(0, 1.5fr) minmax(320px, 0.8fr);
            gap: 20px;
            align-items: stretch;
            margin-bottom: 20px;
        }
        .hero-card,
        .card,
        .info-panel,
        .auth-card,
        .alert,
        .table-panel {
            border: 1px solid var(--border);
            border-radius: var(--radius-xl);
            background: var(--surface);
            backdrop-filter: blur(18px);
            box-shadow: var(--shadow-soft);
        }
        .hero-card {
            padding: 28px;
        }
        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(36, 71, 245, 0.08);
            color: var(--primary-strong);
            font-size: 0.82rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        .page-title {
            margin: 16px 0 10px;
            font-size: clamp(2rem, 4vw, 3.35rem);
            line-height: 1;
            letter-spacing: -0.05em;
        }
        .page-subtitle {
            margin: 0;
            max-width: 68ch;
            color: var(--muted);
            font-size: 1rem;
            line-height: 1.65;
        }
        .hero-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 22px;
        }
        .button,
        button,
        input[type="submit"] {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 16px;
            background: var(--surface-strong);
            color: var(--text);
            border: 1px solid var(--border-strong);
        }
        .button.secondary,
        button.secondary {
            background: #ffffff;
        }
        .button.primary,
        button.primary,
        input[type="submit"].primary {
            color: #ffffff;
            border-color: transparent;
            background: linear-gradient(135deg, var(--primary) 0%, #3b66ff 100%);
            box-shadow: 0 12px 24px rgba(36, 71, 245, 0.16);
        }
        .button.ghost,
        button.ghost {
            background: rgba(255, 255, 255, 0.5);
        }
        .button.danger,
        button.danger {
            color: #b42318;
            background: rgba(217, 45, 32, 0.08);
            border-color: rgba(217, 45, 32, 0.16);
        }
        .button:hover,
        button:hover,
        input[type="submit"]:hover {
            transform: translateY(-1px);
        }
        .hero-side {
            display: grid;
            gap: 16px;
        }
        .stat-strip {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }
        .stat-card,
        .mini-card {
            padding: 18px;
            border-radius: var(--radius-lg);
            background: var(--surface-strong);
            border: 1px solid var(--border);
        }
        .stat-card strong,
        .mini-card strong {
            display: block;
            font-size: 1.55rem;
            letter-spacing: -0.05em;
        }
        .stat-card span,
        .mini-card span {
            display: block;
            margin-top: 5px;
            color: var(--muted);
            font-size: 0.92rem;
        }
        .section {
            margin-top: 20px;
        }
        .section-head {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
        }
        .section-head h2,
        .section-head h3 {
            margin: 0;
            letter-spacing: -0.04em;
        }
        .section-head p {
            margin: 4px 0 0;
            color: var(--muted);
            line-height: 1.6;
        }
        .grid {
            display: grid;
            gap: 16px;
        }
        .grid.cards-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .grid.cards-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .grid.cards-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .card,
        .table-panel,
        .info-panel {
            padding: 22px;
        }
        .card.compact {
            padding: 18px;
        }
        .card-header {
            display: flex;
            align-items: start;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 16px;
        }
        .card-title {
            margin: 0;
            font-size: 1.1rem;
            letter-spacing: -0.03em;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 10px;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .badge.info { background: rgba(36, 71, 245, 0.1); color: var(--primary-strong); }
        .badge.success { background: rgba(15, 157, 88, 0.1); color: var(--success); }
        .badge.warning { background: rgba(201, 122, 22, 0.1); color: var(--warning); }
        .badge.danger { background: rgba(217, 45, 32, 0.1); color: var(--danger); }
        .badge.muted { background: rgba(102, 112, 133, 0.1); color: var(--muted); }
        .list {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        .list li {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            padding: 12px 0;
            border-top: 1px solid var(--border);
        }
        .list li:first-child { border-top: 0; padding-top: 0; }
        .list .label {
            color: var(--muted);
        }
        .list .value {
            text-align: right;
            font-weight: 700;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        .table th,
        .table td {
            padding: 14px 10px;
            border-bottom: 1px solid var(--border);
            text-align: left;
            vertical-align: top;
        }
        .table th {
            color: var(--muted);
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }
        .table td:last-child,
        .table th:last-child { text-align: right; }
        .stack {
            display: grid;
            gap: 12px;
        }
        .activation-card {
            background: linear-gradient(135deg, rgba(36, 71, 245, 0.06), rgba(255, 255, 255, 0.92));
            border-color: rgba(36, 71, 245, 0.16);
        }
        .activation-summary {
            display: grid;
            justify-items: end;
            gap: 3px;
            color: var(--muted);
            font-size: 0.82rem;
            white-space: nowrap;
        }
        .activation-summary strong {
            color: var(--ink);
            font-size: 1.35rem;
            letter-spacing: -0.04em;
        }
        .activation-progress {
            height: 8px;
            margin: 2px 0 18px;
            overflow: hidden;
            border-radius: 999px;
            background: rgba(36, 71, 245, 0.12);
        }
        .activation-progress span {
            display: block;
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, var(--primary), #6d5dfc);
            transition: width 300ms ease;
        }
        .activation-steps {
            display: grid;
            gap: 10px;
        }
        .activation-step {
            display: grid;
            grid-template-columns: 34px minmax(0, 1fr) auto;
            align-items: center;
            gap: 12px;
            padding: 12px 0;
            border-top: 1px solid var(--border);
        }
        .activation-step:first-child { border-top: 0; }
        .step-marker {
            display: grid;
            width: 30px;
            height: 30px;
            place-items: center;
            border: 1px solid rgba(36, 71, 245, 0.2);
            border-radius: 50%;
            color: var(--primary-strong);
            font-size: 0.82rem;
            font-weight: 800;
        }
        .activation-step.is-done .step-marker {
            border-color: rgba(15, 157, 88, 0.2);
            background: rgba(15, 157, 88, 0.1);
            color: var(--success);
        }
        .step-copy {
            display: grid;
            gap: 3px;
            min-width: 0;
        }
        .step-copy strong { letter-spacing: -0.02em; }
        .step-copy span,
        .step-status { color: var(--muted); font-size: 0.88rem; }
        .step-status { color: var(--success); font-weight: 800; }
        .notice,
        .alert {
            padding: 16px 18px;
        }
        .alert {
            margin-bottom: 14px;
        }
        .alert.success {
            border-color: rgba(15, 157, 88, 0.18);
            background: rgba(15, 157, 88, 0.08);
            color: #096c3d;
        }
        .alert.error {
            border-color: rgba(217, 45, 32, 0.18);
            background: rgba(217, 45, 32, 0.08);
            color: #9d1c13;
        }
        .alert.warning {
            border-color: rgba(201, 122, 22, 0.18);
            background: rgba(201, 122, 22, 0.08);
            color: #8f540e;
        }
        .meta {
            color: var(--muted);
            font-size: 0.92rem;
        }
        .form-grid {
            display: grid;
            gap: 14px;
        }
        .field label {
            display: block;
            margin-bottom: 8px;
            color: var(--text);
            font-size: 0.92rem;
            font-weight: 700;
        }
        .field .hint {
            margin-top: 8px;
            color: var(--muted);
            font-size: 0.88rem;
            line-height: 1.55;
        }
        .paste-zone {
            display: grid;
            gap: 4px;
            margin-bottom: 10px;
            padding: 14px 16px;
            border-radius: 16px;
            border: 1px dashed rgba(36, 71, 245, 0.28);
            background: rgba(36, 71, 245, 0.05);
            color: var(--text);
            outline: none;
            cursor: pointer;
            transition: border-color 160ms ease, background 160ms ease, box-shadow 160ms ease, transform 160ms ease;
        }
        .paste-zone:hover,
        .paste-zone:focus-visible {
            border-color: rgba(36, 71, 245, 0.52);
            background: rgba(36, 71, 245, 0.08);
            box-shadow: 0 0 0 4px rgba(36, 71, 245, 0.08);
            transform: translateY(-1px);
        }
        .paste-zone strong {
            font-size: 0.95rem;
            letter-spacing: -0.02em;
        }
        .paste-zone span {
            color: var(--muted);
            font-size: 0.88rem;
            line-height: 1.45;
        }
        input[type="text"],
        input[type="file"],
        input[type="email"],
        input[type="password"],
        input[type="url"],
        input[type="datetime-local"],
        textarea,
        select {
            width: 100%;
            padding: 13px 14px;
            border-radius: 14px;
            border: 1px solid var(--border-strong);
            background: rgba(255, 255, 255, 0.9);
            color: var(--text);
            outline: none;
            font: inherit;
            transition: border-color 160ms ease, box-shadow 160ms ease;
        }
        input:focus,
        textarea:focus,
        select:focus {
            border-color: rgba(36, 71, 245, 0.5);
            box-shadow: 0 0 0 4px rgba(36, 71, 245, 0.12);
        }
        .actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }
        .prose p { line-height: 1.7; }
        .empty {
            padding: 24px;
            border-radius: var(--radius-lg);
            background: rgba(255, 255, 255, 0.72);
            border: 1px dashed rgba(102, 112, 133, 0.25);
            color: var(--muted);
        }
        .kpi {
            display: flex;
            align-items: baseline;
            gap: 8px;
        }
        .kpi strong {
            font-size: 1.65rem;
            letter-spacing: -0.05em;
        }
        .kpi span {
            color: var(--muted);
            font-size: 0.92rem;
        }
        code {
            padding: 2px 6px;
            border-radius: 8px;
            background: rgba(16, 24, 40, 0.06);
        }
        pre {
            margin: 0;
            padding: 18px;
            overflow: auto;
            border-radius: 18px;
            background: #081120;
            color: #dbeafe;
            border: 1px solid rgba(148, 163, 184, 0.16);
        }
        @media (max-width: 980px) {
            .hero,
            .grid.cards-4,
            .grid.cards-3,
            .grid.cards-2 {
                grid-template-columns: 1fr;
            }
            .topbar {
                flex-direction: column;
                align-items: stretch;
            }
            .nav {
                justify-content: flex-start;
            }
        }
        @media (max-width: 640px) {
            .app-shell {
                width: min(100% - 18px, 1200px);
                padding-top: 10px;
            }
            .hero-card,
            .card,
            .table-panel,
            .info-panel,
            .auth-card {
                padding: 18px;
                border-radius: 20px;
            }
            .page-title {
                font-size: 2.15rem;
            }
            .hero-actions,
            .actions {
                flex-direction: column;
                align-items: stretch;
            }
            .button,
            button,
            input[type="submit"],
            .nav-link,
            .nav-pill {
                width: 100%;
            }
            .table th,
            .table td {
                padding: 12px 8px;
            }
            .activation-step { grid-template-columns: 30px minmax(0, 1fr); }
            .activation-step .button,
            .activation-step .step-status { grid-column: 2; justify-self: start; }
        }
    </style>
</head>
<body>
<div class="app-shell">
    @hasSection('marketing_page')
    @else
    <header class="topbar">
        <a class="brand" href="{{ route('dashboard') }}">
            <img class="brand-mark" src="{{ $branding->logoUrl() }}" alt="Logo do painel" loading="eager">
            <div class="brand-title">
                <strong>MElink</strong>
                <span>{{ config('panel.host', 'me.vr766.com') }} · controle operacional</span>
            </div>
        </a>

        <nav class="nav" aria-label="Navegação principal">
            @auth
                <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">Dashboard</a>
                <a class="nav-link {{ request()->routeIs('links.*') ? 'active' : '' }}" href="{{ route('links.index') }}">Links</a>
                <a class="nav-link {{ request()->routeIs('domains.*') ? 'active' : '' }}" href="{{ route('domains.index') }}">Domínios</a>
                <a class="nav-link {{ request()->routeIs('billing.*') ? 'active' : '' }}" href="{{ route('billing.index') }}">Assinatura</a>
                <a class="nav-link {{ request()->routeIs('workspaces.*') ? 'active' : '' }}" href="{{ route('workspaces.index') }}">Workspaces</a>
                <a class="nav-link {{ request()->routeIs('api-tokens.*') ? 'active' : '' }}" href="{{ route('api-tokens.index') }}">API</a>
                @if (auth()->user()?->isOwner())
                    <a class="nav-link {{ request()->routeIs('admin.*') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">Admin</a>
                    <a class="nav-link {{ request()->routeIs('admin.branding.*') ? 'active' : '' }}" href="{{ route('admin.branding.edit') }}">Marca</a>
                    <a class="nav-link {{ request()->routeIs('admin.audit-logs.*') ? 'active' : '' }}" href="{{ route('admin.audit-logs.index') }}">Auditoria</a>
                    <a class="nav-link {{ request()->routeIs('admin.rich-previews.*') ? 'active' : '' }}" href="{{ route('admin.rich-previews.index') }}">Rich Preview</a>
                @endif
                <form method="POST" action="{{ route('logout') }}" style="display:inline;">
                    @csrf
                    <button type="submit" class="nav-pill">Sair</button>
                </form>
            @else
                <a class="nav-link {{ request()->routeIs('login') ? 'active' : '' }}" href="{{ route('login') }}">Entrar</a>
                <a class="nav-pill" href="{{ route('register') }}">Criar conta</a>
            @endauth
        </nav>
    </header>
    @endif

    <main class="{{ trim($__env->yieldContent('marketing_page')) !== '' ? 'marketing-main' : 'page' }}">
        @if (session('status'))
            <div class="alert success" role="status">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert error" role="alert">
                <strong>Revisar os pontos abaixo:</strong>
                <ul style="margin: 10px 0 0 18px; padding: 0;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>
</div>
</body>
</html>
