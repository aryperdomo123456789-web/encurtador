<?php

return [
    'base_url' => env('SHLINK_BASE_URL', 'https://api-shlink.vr766.com'),
    // O fallback deve preferir a rede interna Docker para não chamar o proxy
    // público com Host do painel e criar recursão me.vr766.com -> Laravel.
    'redirect_base_url' => env('SHLINK_REDIRECT_BASE_URL', env('SHLINK_BASE_URL', 'https://api-shlink.vr766.com')),
    'api_key' => env('SHLINK_API_KEY', ''),
    'api_version' => (int) env('SHLINK_API_VERSION', 3),
    'timeout' => (int) env('SHLINK_TIMEOUT', 20),
    'redirect_connect_timeout' => (float) env('SHLINK_REDIRECT_CONNECT_TIMEOUT', 1.0),
    'redirect_timeout' => (float) env('SHLINK_REDIRECT_TIMEOUT', 3.0),
    'redirect_rate_limit' => (int) env('SHLINK_REDIRECT_RATE_LIMIT', 120),
    'redirect_max_path_length' => (int) env('SHLINK_REDIRECT_MAX_PATH_LENGTH', 160),
    'redirect_blocked_patterns' => array_values(array_filter(array_map(
        static fn (string $pattern): string => trim($pattern),
        explode(',', (string) env(
            'SHLINK_REDIRECT_BLOCKED_PATTERNS',
            '/(?:^|\\/)\\.(?:git|env|svn|hg)(?:\\/|$)/i,/(?:^|\\/)(?:wp-admin|wp-includes|xmlrpc\\.php|webshell|shell)(?:\\/|$)/i,/\\.(?:php[0-9]?|env|bak|backup|old|orig|sql|sqlite|log|yml|yaml|ini|conf)$/i'
        ))
    ), static fn (string $pattern): bool => $pattern !== '')),
    'free_monthly_limit' => (int) env('FREE_MONTHLY_LINK_LIMIT', 5),
    'default_domain' => env('SHLINK_DEFAULT_DOMAIN', 'me.vr766.com'),
];
