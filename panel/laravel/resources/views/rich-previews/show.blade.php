<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php($socialImageUrl = $richPreview->socialImageUrl())
    <title>{{ $richPreview->title }} | {{ config('app.name', 'MElink') }}</title>
    <meta name="description" content="{{ $richPreview->excerpt() }}">
    <meta property="og:title" content="{{ $richPreview->title }}">
    <meta property="og:description" content="{{ $richPreview->excerpt() }}">
    <meta property="og:image" content="{{ $socialImageUrl }}">
    <meta property="og:image:secure_url" content="{{ $socialImageUrl }}">
    <meta property="og:url" content="{{ $richPreview->previewUrl() }}">
    <meta property="og:type" content="website">
    <meta property="og:image:alt" content="{{ $richPreview->title }}">
    @if ($richPreview->socialImageIsOptimized())
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta property="og:image:type" content="image/jpeg">
    @endif
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $richPreview->title }}">
    <meta name="twitter:description" content="{{ $richPreview->excerpt() }}">
    <meta name="twitter:image" content="{{ $socialImageUrl }}">
    <meta name="twitter:image:alt" content="{{ $richPreview->title }}">
    <link rel="canonical" href="{{ $richPreview->previewUrl() }}">
    <meta name="theme-color" content="#2447f5">
    <style>
        :root {
            color-scheme: light;
            --bg: #f4f7fb;
            --surface: rgba(255, 255, 255, 0.92);
            --text: #101828;
            --muted: #667085;
            --border: rgba(16, 24, 40, 0.1);
            --primary: #2447f5;
            --primary-strong: #1732b3;
            --shadow: 0 24px 80px rgba(15, 23, 42, 0.15);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Manrope", "Avenir Next", "Segoe UI", sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at top left, rgba(36, 71, 245, 0.16), transparent 28%),
                radial-gradient(circle at bottom right, rgba(15, 157, 88, 0.10), transparent 32%),
                linear-gradient(180deg, #ffffff 0%, var(--bg) 100%);
            display: grid;
            place-items: center;
            padding: 24px;
        }
        .shell {
            width: min(100%, 920px);
        }
        .card {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(320px, 1.08fr);
            gap: 0;
            overflow: hidden;
            border-radius: 32px;
            background: var(--surface);
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
            backdrop-filter: blur(16px);
        }
        .content {
            padding: 32px;
            display: grid;
            gap: 20px;
            align-content: start;
        }
        .eyebrow {
            display: inline-flex;
            width: fit-content;
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(36, 71, 245, 0.1);
            color: var(--primary-strong);
            font-size: 0.78rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
        h1 {
            margin: 0;
            font-size: clamp(2rem, 4vw, 3.2rem);
            line-height: 1;
            letter-spacing: -0.05em;
        }
        .title-link {
            color: inherit;
            text-decoration: none;
        }
        .title-link:hover {
            text-decoration: underline;
            text-decoration-thickness: 0.12em;
            text-underline-offset: 0.12em;
        }
        p {
            margin: 0;
            color: var(--muted);
            line-height: 1.65;
        }
        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 6px;
        }
        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 48px;
            padding: 0 18px;
            border-radius: 999px;
            font-weight: 800;
            text-decoration: none;
        }
        .button.primary {
            color: #fff;
            background: linear-gradient(135deg, var(--primary), var(--primary-strong));
            box-shadow: 0 18px 36px rgba(36, 71, 245, 0.24);
        }
        .button.secondary {
            color: var(--text);
            border: 1px solid var(--border);
            background: rgba(255,255,255,0.9);
        }
        .hero-image {
            position: relative;
            min-height: 100%;
            background: #0f172a;
            cursor: pointer;
        }
        .hero-image a,
        .hero-image img {
            display: block;
            width: 100%;
            height: 100%;
        }
        .hero-image img {
            object-fit: cover;
        }
        .hero-image::after {
            content: "";
            position: absolute;
            inset: auto 0 0;
            height: 38%;
            background: linear-gradient(180deg, transparent, rgba(0, 0, 0, 0.42));
            pointer-events: none;
        }
        .image-badge {
            position: absolute;
            left: 20px;
            top: 20px;
            z-index: 1;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: 999px;
            color: #fff;
            background: rgba(15, 23, 42, 0.55);
            backdrop-filter: blur(10px);
            font-weight: 700;
        }
        .meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            color: var(--muted);
            font-size: 0.92rem;
        }
        .chip {
            display: inline-flex;
            padding: 6px 10px;
            border-radius: 999px;
            background: rgba(16, 24, 40, 0.06);
            font-size: 0.86rem;
        }
        @media (max-width: 860px) {
            .card {
                grid-template-columns: 1fr;
            }
            .hero-image {
                min-height: 260px;
                order: -1;
            }
        }
    </style>
</head>
<body>
    <main class="shell">
        <article class="card">
            <section class="content">
                <span class="eyebrow">Rich Preview</span>
                <h1><a class="title-link" href="{{ $richPreview->goUrl() }}">{{ $richPreview->title }}</a></h1>
                <p>{{ $richPreview->excerpt() }}</p>

                <div class="meta">
                    <span class="chip">Prévia pública</span>
                    <span class="chip">{{ $richPreview->click_count }} cliques</span>
                    <span class="chip">{{ $richPreview->slug }}</span>
                    @if ($richPreview->campaignLabel() !== '')
                        <span class="chip">{{ $richPreview->campaignLabel() }}</span>
                    @endif
                    @if ($richPreview->categoryLabel() !== '')
                        <span class="chip">{{ $richPreview->categoryLabel() }}</span>
                    @endif
                </div>

                <div class="actions">
                    <a class="button primary" href="{{ $richPreview->goUrl() }}">{{ $richPreview->cta_label }}</a>
                    <a class="button secondary" href="{{ $richPreview->goUrl() }}">Abrir destino final</a>
                </div>

                <p style="font-size:0.96rem;">
                    Toque na imagem, no título ou no botão para seguir para o link final.
                </p>
            </section>

            <aside class="hero-image">
                <a href="{{ $richPreview->goUrl() }}" aria-label="Abrir {{ $richPreview->title }}">
                    <img src="{{ $socialImageUrl }}" alt="{{ $richPreview->title }}" loading="eager" decoding="async">
                </a>
                <div class="image-badge">
                    <span>Toque para abrir</span>
                </div>
            </aside>
        </article>
    </main>
</body>
</html>
