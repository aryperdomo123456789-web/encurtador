@extends('layouts.app')

@section('title', 'MElink — links que trabalham pelo seu negócio')
@section('meta_description', 'Crie links de marca, organize campanhas, gere QR Codes e entenda o que acontece depois de cada clique com o MElink.')
@section('marketing_page', '1')

@section('content')
<style>
    .mk-page {
        --mk-ink: #101321;
        --mk-muted: #5b6377;
        --mk-blue: #3157f5;
        --mk-blue-dark: #1734b9;
        --mk-violet: #7046e8;
        --mk-lilac: #e9e6ff;
        --mk-mint: #dcf7e9;
        --mk-yellow: #ffd56a;
        --mk-border: rgba(16, 19, 33, .11);
        --mk-soft: #f7f8fc;
        color: var(--mk-ink);
        font-family: "Manrope", "Avenir Next", "Segoe UI", sans-serif;
        overflow: hidden;
    }

    .mk-page * { box-sizing: border-box; }
    .mk-page a { color: inherit; }
    .mk-container { width: min(1180px, calc(100% - 40px)); margin: 0 auto; }
    .mk-announcement {
        display: flex;
        justify-content: center;
        gap: 10px;
        padding: 10px 20px;
        color: #fff;
        background: var(--mk-ink);
        font-size: .78rem;
        font-weight: 800;
        letter-spacing: .01em;
        text-align: center;
    }
    .mk-announcement a { color: #bfc8ff; text-decoration: underline; text-underline-offset: 3px; }
    .mk-nav {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 26px;
        padding: 24px 0 18px;
    }
    .mk-brand { display: inline-flex; align-items: center; gap: 11px; font-weight: 900; letter-spacing: -.04em; }
    .mk-brand img { width: 40px; height: 40px; object-fit: cover; border-radius: 13px; box-shadow: 0 10px 22px rgba(49, 87, 245, .18); }
    .mk-brand-text { display: grid; gap: 2px; font-size: 1.06rem; }
    .mk-brand-text small { color: var(--mk-muted); font-size: .67rem; font-weight: 700; letter-spacing: .01em; }
    .mk-nav-links { display: flex; align-items: center; gap: 24px; margin-left: auto; color: #464d60; font-size: .88rem; font-weight: 750; }
    .mk-nav-links a { transition: color .18s ease; }
    .mk-nav-links a:hover { color: var(--mk-blue); }
    .mk-nav-actions { display: flex; align-items: center; gap: 10px; }
    .mk-link-btn, .mk-btn { display: inline-flex; align-items: center; justify-content: center; gap: 9px; border-radius: 999px; font-size: .86rem; font-weight: 850; transition: transform .18s ease, box-shadow .18s ease, background .18s ease; }
    .mk-link-btn { padding: 11px 14px; color: #3d455a; }
    .mk-link-btn:hover, .mk-btn:hover { transform: translateY(-2px); }
    .mk-btn { padding: 14px 20px; }
    .mk-btn-primary { color: #fff; background: var(--mk-blue); box-shadow: 0 16px 26px rgba(49, 87, 245, .2); }
    .mk-btn-primary:hover { background: var(--mk-blue-dark); box-shadow: 0 18px 32px rgba(49, 87, 245, .28); }
    .mk-btn-dark { color: #fff; background: var(--mk-ink); }
    .mk-btn-light { color: var(--mk-ink); background: #fff; border: 1px solid var(--mk-border); }
    .mk-btn-light:hover { box-shadow: 0 10px 25px rgba(16, 19, 33, .1); }
    .mk-hero { padding: 42px 0 68px; }
    .mk-hero-grid { display: grid; grid-template-columns: minmax(0, .95fr) minmax(0, 1.05fr); align-items: center; gap: 64px; }
    .mk-kicker { display: inline-flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 999px; color: var(--mk-blue-dark); background: rgba(49, 87, 245, .1); font-size: .72rem; font-weight: 900; letter-spacing: .1em; text-transform: uppercase; }
    .mk-kicker::before { content: ""; width: 7px; height: 7px; border-radius: 50%; background: #2bc275; box-shadow: 0 0 0 4px rgba(43, 194, 117, .14); }
    .mk-hero h1 { max-width: 660px; margin: 20px 0 18px; font-size: clamp(3rem, 6vw, 5.7rem); line-height: .94; letter-spacing: -.08em; }
    .mk-hero h1 span { color: var(--mk-blue); }
    .mk-hero-copy { max-width: 590px; margin: 0; color: var(--mk-muted); font-size: 1.12rem; line-height: 1.65; }
    .mk-hero-actions { display: flex; flex-wrap: wrap; gap: 11px; margin-top: 28px; }
    .mk-note { display: flex; align-items: center; gap: 8px; margin-top: 17px; color: #7a8293; font-size: .76rem; font-weight: 700; }
    .mk-note strong { color: #3e4658; }
    .mk-hero-media { position: relative; min-height: 520px; }
    .mk-hero-photo { position: absolute; inset: 0 0 28px 20px; width: calc(100% - 20px); height: calc(100% - 28px); object-fit: cover; border-radius: 32px; box-shadow: 0 30px 70px rgba(54, 56, 112, .2); }
    .mk-hero-photo::after { content: ""; position: absolute; inset: 0; }
    .mk-hero-glow { position: absolute; width: 160px; height: 160px; top: -22px; right: -28px; border-radius: 50%; background: var(--mk-yellow); filter: blur(1px); opacity: .95; z-index: -1; }
    .mk-dashboard-card { position: absolute; right: -25px; bottom: 0; width: min(300px, 56%); padding: 18px; border: 1px solid rgba(255, 255, 255, .75); border-radius: 20px; background: rgba(255, 255, 255, .93); box-shadow: 0 24px 54px rgba(39, 42, 86, .22); backdrop-filter: blur(20px); }
    .mk-dashboard-card header { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 16px; }
    .mk-dashboard-card header span { color: #7f8798; font-size: .66rem; font-weight: 800; }
    .mk-dashboard-card header strong { display: block; font-size: .86rem; }
    .mk-live { display: inline-flex; align-items: center; gap: 5px; color: #148b59; font-size: .66rem; font-weight: 850; }
    .mk-live::before { content: ""; width: 6px; height: 6px; border-radius: 50%; background: #28bb78; }
    .mk-card-kpi { padding: 13px; border-radius: 14px; background: #f3f5ff; }
    .mk-card-kpi small { color: #68728a; font-size: .66rem; font-weight: 800; }
    .mk-card-kpi strong { display: block; margin-top: 4px; font-size: 1.7rem; letter-spacing: -.06em; }
    .mk-card-kpi em { color: #15925d; font-size: .68rem; font-style: normal; font-weight: 900; }
    .mk-bars { display: flex; align-items: end; gap: 6px; height: 62px; margin-top: 14px; padding-top: 10px; }
    .mk-bars i { flex: 1; border-radius: 6px 6px 2px 2px; background: linear-gradient(180deg, var(--mk-blue), #a6b5ff); }
    .mk-bars i:nth-child(1) { height: 34%; } .mk-bars i:nth-child(2) { height: 52%; } .mk-bars i:nth-child(3) { height: 42%; } .mk-bars i:nth-child(4) { height: 70%; } .mk-bars i:nth-child(5) { height: 58%; } .mk-bars i:nth-child(6) { height: 92%; } .mk-bars i:nth-child(7) { height: 78%; }
    .mk-float-tag { position: absolute; top: 24px; left: -5px; display: flex; align-items: center; gap: 9px; padding: 11px 13px; border: 1px solid rgba(255,255,255,.75); border-radius: 14px; background: rgba(255,255,255,.91); box-shadow: 0 16px 30px rgba(39, 42, 86, .15); font-size: .7rem; font-weight: 900; }
    .mk-float-icon { display: grid; place-items: center; width: 27px; height: 27px; border-radius: 9px; color: #fff; background: var(--mk-violet); }
    .mk-proof { padding: 20px 0 42px; border-top: 1px solid var(--mk-border); border-bottom: 1px solid var(--mk-border); }
    .mk-proof-label { margin: 0 0 17px; color: #8a91a0; font-size: .7rem; font-weight: 900; letter-spacing: .09em; text-align: center; text-transform: uppercase; }
    .mk-proof-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
    .mk-proof-pill { display: flex; align-items: center; justify-content: center; gap: 9px; min-height: 50px; padding: 12px; border-radius: 15px; background: rgba(255, 255, 255, .64); color: #394156; font-size: .79rem; font-weight: 850; }
    .mk-proof-pill b { display: grid; place-items: center; width: 26px; height: 26px; border-radius: 9px; color: var(--mk-blue); background: var(--mk-lilac); font-size: .76rem; }
    .mk-section { padding: 108px 0; }
    .mk-section-soft { background: rgba(255,255,255,.52); }
    .mk-section-head { max-width: 760px; margin-bottom: 44px; }
    .mk-section-head.center { margin-right: auto; margin-left: auto; text-align: center; }
    .mk-section-head h2 { margin: 13px 0 14px; font-size: clamp(2.25rem, 4vw, 4.3rem); line-height: .98; letter-spacing: -.075em; }
    .mk-section-head p { max-width: 650px; margin: 0; color: var(--mk-muted); font-size: 1.04rem; line-height: 1.65; }
    .mk-section-head.center p { margin-right: auto; margin-left: auto; }
    .mk-dark { color: #fff; background: var(--mk-ink); }
    .mk-dark .mk-section-head p { color: #aeb5c8; }
    .mk-dark .mk-kicker { color: #c9d2ff; background: rgba(131, 149, 255, .15); }
    .mk-split { display: grid; grid-template-columns: minmax(0, .8fr) minmax(0, 1.2fr); gap: 80px; align-items: start; }
    .mk-feature-list { display: grid; gap: 10px; }
    .mk-feature { display: grid; grid-template-columns: 52px 1fr; gap: 15px; padding: 20px; border: 1px solid var(--mk-border); border-radius: 19px; background: rgba(255,255,255,.7); }
    .mk-feature-icon { display: grid; place-items: center; width: 46px; height: 46px; border-radius: 15px; color: var(--mk-blue); background: var(--mk-lilac); font-weight: 950; }
    .mk-feature h3 { margin: 2px 0 6px; font-size: 1rem; letter-spacing: -.025em; }
    .mk-feature p { margin: 0; color: var(--mk-muted); font-size: .86rem; line-height: 1.55; }
    .mk-steps { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }
    .mk-step { padding: 25px; border-radius: 22px; background: #24283b; }
    .mk-step-num { color: var(--mk-yellow); font-size: .75rem; font-weight: 900; letter-spacing: .1em; }
    .mk-step h3 { margin: 45px 0 9px; font-size: 1.17rem; letter-spacing: -.03em; }
    .mk-step p { margin: 0; color: #aab2c5; font-size: .87rem; line-height: 1.55; }
    .mk-module-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 13px; }
    .mk-module { min-height: 190px; padding: 23px; border: 1px solid var(--mk-border); border-radius: 21px; background: #fff; box-shadow: 0 12px 30px rgba(26, 35, 73, .04); }
    .mk-module:nth-child(2) { background: #e9e6ff; }
    .mk-module:nth-child(3) { background: #dcf7e9; }
    .mk-module:nth-child(4) { color: #fff; background: var(--mk-blue); }
    .mk-module-icon { font-size: 1.35rem; }
    .mk-module h3 { margin: 42px 0 7px; font-size: 1.06rem; letter-spacing: -.035em; }
    .mk-module p { margin: 0; color: #677084; font-size: .82rem; line-height: 1.5; }
    .mk-module:nth-child(4) p { color: #dce3ff; }
    .mk-usecases { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; }
    .mk-usecase { position: relative; min-height: 260px; padding: 25px; overflow: hidden; border-radius: 24px; background: #fff; }
    .mk-usecase::after { content: ""; position: absolute; right: -34px; bottom: -50px; width: 160px; height: 160px; border-radius: 50%; background: var(--mk-lilac); }
    .mk-usecase:nth-child(2)::after { background: var(--mk-mint); }
    .mk-usecase:nth-child(3)::after { background: #ffe9bb; }
    .mk-usecase small { color: var(--mk-blue); font-size: .68rem; font-weight: 900; letter-spacing: .1em; text-transform: uppercase; }
    .mk-usecase h3 { max-width: 230px; margin: 58px 0 10px; font-size: 1.28rem; line-height: 1.05; letter-spacing: -.05em; }
    .mk-usecase p { position: relative; z-index: 1; max-width: 260px; margin: 0; color: var(--mk-muted); font-size: .86rem; line-height: 1.55; }
    .mk-pricing { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; max-width: 900px; margin: 0 auto; }
    .mk-price-card { padding: 30px; border: 1px solid var(--mk-border); border-radius: 26px; background: #fff; }
    .mk-price-card.featured { color: #fff; border-color: var(--mk-blue); background: var(--mk-blue); box-shadow: 0 24px 50px rgba(49, 87, 245, .2); }
    .mk-price-top { display: flex; align-items: center; justify-content: space-between; gap: 10px; }
    .mk-price-top h3 { margin: 0; font-size: 1.35rem; letter-spacing: -.045em; }
    .mk-price-tag { padding: 7px 10px; border-radius: 999px; color: var(--mk-blue-dark); background: var(--mk-lilac); font-size: .64rem; font-weight: 900; text-transform: uppercase; }
    .featured .mk-price-tag { color: var(--mk-ink); background: var(--mk-yellow); }
    .mk-price-card > p { min-height: 48px; margin: 18px 0 20px; color: var(--mk-muted); font-size: .88rem; line-height: 1.55; }
    .featured > p { color: #e0e6ff; }
    .mk-price-list { display: grid; gap: 12px; margin: 0 0 26px; padding: 0; list-style: none; }
    .mk-price-list li { display: flex; gap: 9px; align-items: flex-start; font-size: .83rem; font-weight: 750; }
    .mk-price-list li::before { content: "✓"; color: #1ca96c; font-weight: 950; }
    .featured .mk-price-list li::before { color: var(--mk-yellow); }
    .mk-price-card .mk-btn { width: 100%; }
    .mk-testimonial { display: grid; grid-template-columns: 1.2fr .8fr; gap: 20px; align-items: stretch; }
    .mk-quote { padding: 38px; border-radius: 26px; color: #fff; background: linear-gradient(135deg, #263059, #161a2c); }
    .mk-quote-mark { color: var(--mk-yellow); font-size: 3rem; line-height: .5; }
    .mk-quote p { max-width: 680px; margin: 25px 0; font-size: clamp(1.3rem, 2.5vw, 2rem); line-height: 1.2; letter-spacing: -.05em; }
    .mk-quote small { color: #b7c0d4; font-size: .77rem; font-weight: 800; }
    .mk-proof-card { display: grid; align-content: center; padding: 30px; border-radius: 26px; background: var(--mk-yellow); }
    .mk-proof-card strong { font-size: 3.5rem; line-height: .9; letter-spacing: -.09em; }
    .mk-proof-card span { margin-top: 14px; color: #5a450e; font-size: .84rem; font-weight: 850; line-height: 1.45; }
    .mk-faq { display: grid; grid-template-columns: .75fr 1.25fr; gap: 80px; }
    .mk-faq-list { display: grid; gap: 9px; }
    .mk-faq-list details { padding: 18px 20px; border: 1px solid var(--mk-border); border-radius: 15px; background: rgba(255,255,255,.72); }
    .mk-faq-list summary { cursor: pointer; list-style: none; font-size: .91rem; font-weight: 850; }
    .mk-faq-list summary::-webkit-details-marker { display: none; }
    .mk-faq-list summary::after { content: "+"; float: right; color: var(--mk-blue); font-size: 1.2rem; }
    .mk-faq-list details[open] summary::after { content: "–"; }
    .mk-faq-list details p { margin: 14px 24px 0 0; color: var(--mk-muted); font-size: .84rem; line-height: 1.6; }
    .mk-cta { position: relative; padding: 75px 30px; overflow: hidden; border-radius: 30px; color: #fff; background: var(--mk-blue); text-align: center; }
    .mk-cta::before, .mk-cta::after { content: ""; position: absolute; border-radius: 50%; border: 1px solid rgba(255,255,255,.22); }
    .mk-cta::before { width: 370px; height: 370px; top: -220px; left: -100px; }
    .mk-cta::after { width: 500px; height: 500px; right: -170px; bottom: -350px; }
    .mk-cta > * { position: relative; z-index: 1; }
    .mk-cta h2 { max-width: 700px; margin: 0 auto 16px; font-size: clamp(2.4rem, 5vw, 4.5rem); line-height: .96; letter-spacing: -.08em; }
    .mk-cta p { max-width: 560px; margin: 0 auto; color: #dce3ff; line-height: 1.6; }
    .mk-cta .mk-hero-actions { justify-content: center; }
    .mk-footer { display: flex; justify-content: space-between; gap: 20px; padding: 42px 0 30px; color: #697287; font-size: .76rem; }
    .mk-footer-links { display: flex; gap: 18px; flex-wrap: wrap; }
    .mk-footer a:hover { color: var(--mk-blue); }
    @media (max-width: 980px) {
        .mk-nav-links { display: none; }
        .mk-hero-grid, .mk-split, .mk-faq { grid-template-columns: 1fr; gap: 44px; }
        .mk-hero { padding-top: 24px; }
        .mk-hero-media { min-height: 470px; max-width: 720px; width: 100%; margin: 0 auto; }
        .mk-module-grid { grid-template-columns: repeat(2, 1fr); }
        .mk-steps { grid-template-columns: 1fr; }
        .mk-testimonial { grid-template-columns: 1fr; }
    }
    @media (max-width: 640px) {
        .mk-container { width: min(100% - 24px, 1180px); }
        .mk-announcement { font-size: .68rem; }
        .mk-nav { padding-top: 15px; }
        .mk-nav-actions .mk-link-btn { display: none; }
        .mk-nav-actions .mk-btn { padding: 11px 14px; font-size: .75rem; }
        .mk-hero h1 { font-size: 3.25rem; }
        .mk-hero-copy { font-size: .98rem; }
        .mk-hero-media { min-height: 370px; }
        .mk-hero-photo { inset: 0 0 18px 0; width: 100%; height: calc(100% - 18px); border-radius: 22px; }
        .mk-dashboard-card { right: 8px; width: 230px; padding: 13px; }
        .mk-float-tag { left: 8px; top: 14px; }
        .mk-proof-row, .mk-module-grid, .mk-usecases, .mk-pricing { grid-template-columns: 1fr; }
        .mk-proof-pill { justify-content: flex-start; }
        .mk-section { padding: 75px 0; }
        .mk-section-head h2 { font-size: 2.6rem; }
        .mk-quote { padding: 26px; }
        .mk-proof-card { min-height: 180px; }
        .mk-footer { flex-direction: column; }
    }
</style>

<div class="mk-page">
    <div class="mk-announcement">
        <span>Links de marca. Campanhas mais inteligentes. Crescimento mais mensurável.</span>
        <a href="#produto">Conheça a plataforma →</a>
    </div>

    <div class="mk-container">
        <nav class="mk-nav" aria-label="Navegação principal">
            <a class="mk-brand" href="{{ route('dashboard') }}" aria-label="MElink — início">
                <img src="{{ $branding->logoUrl() }}" alt="MElink" loading="eager">
                <span class="mk-brand-text">MElink <small>links que trabalham</small></span>
            </a>
            <div class="mk-nav-links">
                <a href="#produto">Produto</a>
                <a href="#como-funciona">Como funciona</a>
                <a href="#para-quem">Para quem é</a>
                <a href="#planos">Planos</a>
                <a href="#faq">FAQ</a>
            </div>
            <div class="mk-nav-actions">
                <a class="mk-link-btn" href="{{ route('login') }}">Entrar</a>
                <a class="mk-btn mk-btn-dark" href="{{ route('register') }}">Começar grátis <span aria-hidden="true">↗</span></a>
            </div>
        </nav>

        <main>
            <section class="mk-hero" aria-labelledby="mk-hero-title">
                <div class="mk-hero-grid">
                    <div>
                        <span class="mk-kicker">Plataforma de links para marketing</span>
                        <h1 id="mk-hero-title">Seu link não deveria só redirecionar. <span>Deveria gerar a próxima ação.</span></h1>
                        <p class="mk-hero-copy">Crie links de marca, organize campanhas, gere QR Codes e descubra o que realmente acontece depois de cada clique. Tudo em um painel simples, rápido e feito para quem precisa provar resultado.</p>
                        <div class="mk-hero-actions">
                            <a class="mk-btn mk-btn-primary" href="{{ route('register') }}">Criar conta gratuita <span aria-hidden="true">↗</span></a>
                            <a class="mk-btn mk-btn-light" href="#produto">Ver como funciona <span aria-hidden="true">↓</span></a>
                        </div>
                        <p class="mk-note"><strong>Comece sem cartão.</strong> Seu primeiro link de campanha fica pronto em minutos.</p>
                    </div>
                    <div class="mk-hero-media" aria-label="Prévia ilustrativa do painel de campanhas MElink">
                        <div class="mk-hero-glow"></div>
                        <img class="mk-hero-photo" src="{{ asset('assets/melink-hero-team.png') }}" alt="Equipe de marketing analisando uma campanha em um ambiente de trabalho moderno" loading="eager">
                        <div class="mk-float-tag"><span class="mk-float-icon">↗</span><span>Campanha pronta para medir</span></div>
                        <div class="mk-dashboard-card">
                            <header><div><strong>Exemplo de campanha</strong><span>prévia do painel</span></div><span class="mk-live">Prévia</span></header>
                            <div class="mk-card-kpi"><small>Cliques acompanhados</small><strong>12.480</strong><em>leitura por canal e período</em></div>
                            <div class="mk-bars" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i><i></i></div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="mk-proof" aria-label="Principais capacidades">
                <p class="mk-proof-label">Tudo que você precisa para tirar o link do improviso</p>
                <div class="mk-proof-row">
                    <div class="mk-proof-pill"><b>01</b> Domínio próprio</div>
                    <div class="mk-proof-pill"><b>02</b> QR Code nativo</div>
                    <div class="mk-proof-pill"><b>03</b> Analytics acionável</div>
                    <div class="mk-proof-pill"><b>04</b> Campanhas com UTM</div>
                </div>
            </section>

            <section class="mk-section" id="produto" aria-labelledby="mk-product-title">
                <div class="mk-split">
                    <div class="mk-section-head">
                        <span class="mk-kicker">Menos planilha. Mais clareza.</span>
                        <h2 id="mk-product-title">O link é pequeno. A decisão por trás dele não é.</h2>
                        <p>O MElink transforma cada URL em uma unidade de campanha: com identidade, contexto, rastreio e um caminho claro para melhorar o próximo clique.</p>
                        <div class="mk-hero-actions"><a class="mk-btn mk-btn-primary" href="{{ route('register') }}">Criar meu primeiro link</a></div>
                    </div>
                    <div class="mk-feature-list">
                        <article class="mk-feature"><span class="mk-feature-icon">↗</span><div><h3>Links que passam confiança</h3><p>Use slug personalizado e domínio próprio para sua marca aparecer antes mesmo do clique.</p></div></article>
                        <article class="mk-feature"><span class="mk-feature-icon">◌</span><div><h3>Campanhas que você consegue explicar</h3><p>UTMs, tags e contexto reunidos no mesmo lugar, sem caçar dados em cinco ferramentas.</p></div></article>
                        <article class="mk-feature"><span class="mk-feature-icon">⌁</span><div><h3>Dados que apontam o próximo movimento</h3><p>Veja desempenho, origem, dispositivos e sinais de campanha em um dashboard feito para agir.</p></div></article>
                        <article class="mk-feature"><span class="mk-feature-icon">▣</span><div><h3>QR Code sem depender de terceiro</h3><p>Gere e compartilhe QR Codes nativos, preservando a URL e a identidade do seu projeto.</p></div></article>
                    </div>
                </div>
            </section>

            <section class="mk-section mk-dark" id="como-funciona" aria-labelledby="mk-process-title">
                <div class="mk-container">
                    <div class="mk-section-head">
                        <span class="mk-kicker">Do primeiro link ao próximo insight</span>
                        <h2 id="mk-process-title">Um fluxo curto para uma operação mais inteligente.</h2>
                        <p>Você não precisa de um curso para entender o MElink. O produto guia o caminho e deixa a complexidade onde ela deve ficar: por baixo do capô.</p>
                    </div>
                    <div class="mk-steps">
                        <article class="mk-step"><span class="mk-step-num">01 / CRIAR</span><h3>Monte o link certo</h3><p>Escolha o destino, personalize o slug e adicione o contexto da campanha.</p></article>
                        <article class="mk-step"><span class="mk-step-num">02 / COMPARTILHAR</span><h3>Leve a marca para cada canal</h3><p>Use o link em anúncios, bio, WhatsApp, materiais impressos ou QR Code.</p></article>
                        <article class="mk-step"><span class="mk-step-num">03 / APRENDER</span><h3>Veja o que merece escala</h3><p>Leia os sinais, compare períodos e tome a decisão com dados — não com palpite.</p></article>
                    </div>
                </div>
            </section>

            <section class="mk-section mk-section-soft" aria-labelledby="mk-modules-title">
                <div class="mk-section-head center">
                    <span class="mk-kicker">Uma plataforma. Quatro alavancas.</span>
                    <h2 id="mk-modules-title">Tudo conectado ao resultado.</h2>
                    <p>O melhor dos grandes produtos de links, organizado para você começar pequeno e operar como gente grande.</p>
                </div>
                <div class="mk-module-grid">
                    <article class="mk-module"><span class="mk-module-icon">✦</span><h3>Marca</h3><p>Domínios próprios, slugs memoráveis e uma presença que não parece improvisada.</p></article>
                    <article class="mk-module"><span class="mk-module-icon">◒</span><h3>Campanha</h3><p>UTM builder, tags e organização para separar oferta, canal e objetivo.</p></article>
                    <article class="mk-module"><span class="mk-module-icon">⌁</span><h3>Métrica</h3><p>Analytics compreensível para saber onde o tráfego está respondendo.</p></article>
                    <article class="mk-module"><span class="mk-module-icon">↗</span><h3>Escala</h3><p>Base pronta para equipes, clientes, API e operação de agência.</p></article>
                </div>
            </section>

            <section class="mk-section" id="para-quem" aria-labelledby="mk-audience-title">
                <div class="mk-section-head center">
                    <span class="mk-kicker">Feito para quem tem algo a mover</span>
                    <h2 id="mk-audience-title">Seu link muda. Sua meta continua clara.</h2>
                    <p>Se o trabalho envolve atenção, aquisição ou venda, o MElink ajuda a transformar clique solto em aprendizado de campanha.</p>
                </div>
                <div class="mk-usecases">
                    <article class="mk-usecase"><small>Marketing & tráfego</small><h3>Saiba qual anúncio merece mais verba.</h3><p>Organize UTMs e compare canais sem perder o contexto da campanha.</p></article>
                    <article class="mk-usecase"><small>Agências & clientes</small><h3>Entregue links com cara de operação profissional.</h3><p>Centralize domínios, campanhas e relatórios em uma rotina que escala.</p></article>
                    <article class="mk-usecase"><small>Creators & negócios</small><h3>Converta cada ponto de contato em caminho.</h3><p>Bio, QR, WhatsApp ou oferta: um link memorável para cada intenção.</p></article>
                </div>
            </section>

            <section class="mk-section mk-section-soft" id="planos" aria-labelledby="mk-plans-title">
                <div class="mk-section-head center">
                    <span class="mk-kicker">Comece no tamanho certo</span>
                    <h2 id="mk-plans-title">Valor real desde o primeiro clique.</h2>
                    <p>Comece validando. Evolua quando a operação pedir mais controle, marca e leitura de dados.</p>
                </div>
                <div class="mk-pricing">
                    <article class="mk-price-card"><div class="mk-price-top"><h3>Free</h3><span class="mk-price-tag">Para começar</span></div><p>O caminho simples para testar uma ideia e publicar seus primeiros links.</p><ul class="mk-price-list"><li>Links com cota mensal</li><li>Expiração automática</li><li>Dashboard operacional</li><li>Fluxo rápido de ativação</li></ul><a class="mk-btn mk-btn-light" href="{{ route('register') }}">Começar grátis</a></article>
                    <article class="mk-price-card featured"><div class="mk-price-top"><h3>Premium</h3><span class="mk-price-tag">Para crescer</span></div><p>Mais controle para campanhas, clientes e operações que precisam provar resultado.</p><ul class="mk-price-list"><li>Slug e domínio próprio</li><li>Campanhas com UTM e tags</li><li>Analytics de campanha</li><li>QR Code nativo e compartilhável</li></ul><a class="mk-btn mk-btn-light" href="{{ route('register') }}">Conhecer o Premium</a></article>
                </div>
            </section>

            <section class="mk-section" aria-labelledby="mk-story-title">
                <div class="mk-testimonial">
                    <div class="mk-quote"><div class="mk-quote-mark">“</div><p>O link deixou de ser o fim do anúncio. Virou o começo da conversa com o cliente.</p><small>Uma visão de produto para times que querem medir antes de escalar.</small></div>
                    <div class="mk-proof-card"><strong>1 link</strong><span>para criar, compartilhar, medir e melhorar — sem espalhar a operação em ferramentas desconectadas.</span></div>
                </div>
            </section>

            <section class="mk-section mk-section-soft" id="faq" aria-labelledby="mk-faq-title">
                <div class="mk-faq">
                    <div class="mk-section-head"><span class="mk-kicker">Sem letra miúda</span><h2 id="mk-faq-title">Perguntas honestas antes de começar.</h2><p>Se ainda ficou alguma dúvida, entre no painel e veja o produto funcionando por dentro.</p><div class="mk-hero-actions"><a class="mk-btn mk-btn-primary" href="{{ route('login') }}">Entrar no painel</a></div></div>
                    <div class="mk-faq-list">
                        <details open><summary>O MElink é só um encurtador?</summary><p>Não. O encurtamento é a porta de entrada. O produto organiza marca, campanha, QR Code e analytics para você entender o que acontece depois do clique.</p></details>
                        <details><summary>Preciso ter um domínio próprio?</summary><p>Não para começar. Você pode validar a operação no fluxo gratuito e conectar um domínio próprio quando quiser elevar a identidade e o controle da campanha.</p></details>
                        <details><summary>Consigo usar em anúncios e UTMs?</summary><p>Sim. O fluxo Premium foi desenhado para campanhas com tags, parâmetros UTM e leitura de desempenho por link.</p></details>
                        <details><summary>O QR Code envia meus dados para terceiros?</summary><p>Não. O QR Code nativo é gerado dentro do MElink para que a URL da sua campanha não precise passar por um gerador externo.</p></details>
                        <details><summary>Como começo?</summary><p>Crie sua conta gratuita, publique o primeiro link e use o painel para organizar a próxima campanha. Sem ritual, sem planilha de 40 colunas.</p></details>
                    </div>
                </div>
            </section>

            <section class="mk-cta" aria-labelledby="mk-cta-title">
                <h2 id="mk-cta-title">Pare de colecionar links. Comece a construir resultado.</h2>
                <p>O próximo clique da sua campanha merece uma operação à altura.</p>
                <div class="mk-hero-actions"><a class="mk-btn mk-btn-dark" href="{{ route('register') }}">Criar conta gratuita <span aria-hidden="true">↗</span></a><a class="mk-btn mk-btn-light" href="{{ route('login') }}">Já tenho uma conta</a></div>
            </section>
        </main>

        <footer class="mk-footer">
            <span>© {{ date('Y') }} MElink. Links que trabalham.</span>
            <div class="mk-footer-links"><a href="{{ route('login') }}">Entrar</a><a href="{{ route('register') }}">Criar conta</a><a href="#faq">Dúvidas</a></div>
        </footer>
    </div>
</div>
@endsection
