<?php

return [
    'base_url' => env('SHLINK_BASE_URL', 'https://api-shlink.vr766.com'),
    'api_key' => env('SHLINK_API_KEY', ''),
    'api_version' => (int) env('SHLINK_API_VERSION', 3),
    'free_monthly_limit' => (int) env('FREE_MONTHLY_LINK_LIMIT', 5),
    'default_domain' => env('SHLINK_DEFAULT_DOMAIN', 'me.vr766.com'),
];
