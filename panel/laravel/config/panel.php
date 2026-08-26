<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Painel — Host
    |--------------------------------------------------------------------------
    |
    | Hostname que responde pelas rotas do painel administrativo. Isola o
    | painel do host público de slugs (motor Shlink), evitando que caminhos
    | como /login, /links ou /domains colidam com short codes.
    |
    | - Em produção, o padrão do projeto é `me.vr766.com`.
    | - Em desenvolvimento local, defina `PANEL_HOST=` (vazio) no `.env`
    |   para desativar o domain guard e servir o painel em qualquer host.
    |
    */

    'host' => env(
        'PANEL_HOST',
        env('APP_ENV', 'local') === 'testing' ? '' : 'me.vr766.com'
    ),

    /*
    |--------------------------------------------------------------------------
    | Painel — Trusted Proxies
    |--------------------------------------------------------------------------
    |
    | O painel roda atrás de proxy reverso (Traefik/Caddy). `*` confia em
    | qualquer proxy da rede interna. Em produção, prefira faixas
    | específicas (`10.0.0.0/8`, `172.16.0.0/12`, etc).
    |
    */

    'trusted_proxies' => env('TRUSTED_PROXIES', '127.0.0.1,::1'),

    'login_rate_limit' => (int) env('PANEL_LOGIN_RATE_LIMIT', 10),
    'register_rate_limit' => (int) env('PANEL_REGISTER_RATE_LIMIT', 5),
    'health_rate_limit' => (int) env('PANEL_HEALTH_RATE_LIMIT', 60),
    'api_rate_limit' => (int) env('PANEL_API_RATE_LIMIT', 120),
    'api_token_expiry_days' => (int) env('PANEL_API_TOKEN_EXPIRY_DAYS', 365),
    'require_email_verification' => (bool) env(
        'PANEL_REQUIRE_EMAIL_VERIFICATION',
        env('APP_ENV', 'local') === 'production'
    ),

    /*
    |--------------------------------------------------------------------------
    | Painel — Custom Domain DNS Target
    |--------------------------------------------------------------------------
    |
    | Alvo que os clientes devem apontar via CNAME (ou A) ao registrar um
    | domínio próprio no painel. A verificação de DNS compara os registros
    | resolvidos com este valor antes de registrar o domínio no Shlink.
    |
    */

    'custom_domain_dns_target' => env('PANEL_CUSTOM_DOMAIN_DNS_TARGET', 'me.vr766.com'),
];
