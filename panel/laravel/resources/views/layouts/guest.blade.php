<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'MElink')</title>
    <meta name="description" content="@yield('meta_description', 'Acesso administrativo ao painel MElink.')">
    <meta property="og:title" content="@yield('title', 'MElink')">
    <meta property="og:description" content="@yield('meta_description', 'Acesso administrativo ao painel MElink.')">
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
            --surface: rgba(255, 255, 255, 0.9);
            --text: #101828;
            --muted: #667085;
            --border: rgba(16, 24, 40, 0.1);
            --border-strong: rgba(16, 24, 40, 0.15);
            --primary: #2447f5;
            --primary-strong: #1732b3;
            --shadow: 0 24px 80px rgba(15, 23, 42, 0.14);
            --radius-xl: 28px;
            --radius-lg: 20px;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            font-family: "Manrope", "Avenir Next", "Segoe UI", sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at top left, rgba(36, 71, 245, 0.15), transparent 28%),
                radial-gradient(circle at bottom right, rgba(15, 157, 88, 0.12), transparent 30%),
                linear-gradient(180deg, #ffffff 0%, var(--bg) 100%);
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
            mask-image: linear-gradient(180deg, rgba(0, 0, 0, 0.55), transparent 90%);
            opacity: 0.45;
        }

        .auth-shell {
            position: relative;
            z-index: 1;
            width: min(100%, 520px);
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 18px;
            text-decoration: none;
            color: inherit;
        }
        .brand-mark {
            width: 48px;
            height: 48px;
            border-radius: 16px;
            object-fit: cover;
            background: #ffffff;
            box-shadow: 0 16px 28px rgba(36, 71, 245, 0.18);
        }
        .brand strong {
            display: block;
            font-size: 1.05rem;
            letter-spacing: -0.02em;
        }
        .brand span {
            display: block;
            color: var(--muted);
            font-size: 0.92rem;
            margin-top: 3px;
        }
        .auth-card {
            padding: 28px;
            border-radius: var(--radius-xl);
            background: var(--surface);
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
            backdrop-filter: blur(16px);
        }
        .eyebrow {
            display: inline-flex;
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(36, 71, 245, 0.08);
            color: var(--primary-strong);
            font-size: 0.8rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        h1 {
            margin: 16px 0 8px;
            font-size: clamp(2rem, 4vw, 2.6rem);
            line-height: 1;
            letter-spacing: -0.05em;
        }
        p {
            margin: 0;
            color: var(--muted);
            line-height: 1.65;
        }
        .stack { display: grid; gap: 16px; }
        .alert {
            padding: 15px 16px;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            background: rgba(255, 255, 255, 0.72);
        }
        .alert.error {
            border-color: rgba(217, 45, 32, 0.18);
            background: rgba(217, 45, 32, 0.08);
            color: #9d1c13;
        }
        .field label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.92rem;
            font-weight: 700;
        }
        input[type="email"],
        input[type="password"],
        input[type="text"] {
            width: 100%;
            padding: 13px 14px;
            border-radius: 14px;
            border: 1px solid var(--border-strong);
            background: rgba(255, 255, 255, 0.95);
            color: var(--text);
            font: inherit;
            outline: none;
        }
        input:focus {
            border-color: rgba(36, 71, 245, 0.5);
            box-shadow: 0 0 0 4px rgba(36, 71, 245, 0.12);
        }
        .remember {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--muted);
            font-size: 0.92rem;
        }
        button {
            width: 100%;
            padding: 13px 16px;
            border-radius: 999px;
            border: 0;
            background: linear-gradient(135deg, var(--primary) 0%, #3b66ff 100%);
            color: #fff;
            font: inherit;
            font-weight: 800;
            cursor: pointer;
            box-shadow: 0 14px 26px rgba(36, 71, 245, 0.18);
        }
        button:hover {
            transform: translateY(-1px);
            background: linear-gradient(135deg, var(--primary-strong) 0%, #2144f0 100%);
        }
        .help {
            margin-top: 14px;
            font-size: 0.92rem;
        }
        .help a {
            color: var(--primary-strong);
            font-weight: 800;
            text-decoration: none;
        }
        @media (max-width: 520px) {
            .auth-card {
                padding: 20px;
                border-radius: 22px;
            }
        }
    </style>
</head>
<body>
<div class="auth-shell">
    <a href="{{ route('dashboard') }}" class="brand">
        <picture>
            <source media="(prefers-color-scheme: dark)" srcset="{{ $branding->logoUrl('dark') }}">
            <img class="brand-mark" src="{{ $branding->logoUrl('light') }}" alt="Logo do painel" loading="eager">
        </picture>
        <div>
            <strong>MElink</strong>
            <span>{{ config('panel.host', 'me.vr766.com') }} · acesso administrativo</span>
        </div>
    </a>

    <section class="auth-card">
        @yield('content')
    </section>
</div>
</body>
</html>
