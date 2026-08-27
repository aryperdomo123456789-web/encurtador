<?php

use App\Http\Middleware\ApiIdempotency;
use App\Http\Middleware\AttachRequestContext;
use App\Http\Middleware\AuthenticateApiToken;
use App\Http\Middleware\EnsureOwnerAdminHost;
use App\Http\Middleware\EnsurePanelHost;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\UseCurrentPanelHost;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $trustedProxies = array_values(array_filter(array_map(
            static fn (string $proxy): string => trim($proxy),
            explode(',', (string) env(
                'PANEL_TRUSTED_PROXIES',
                env('TRUSTED_PROXIES', '127.0.0.1,::1')
            ))
        )));
        $middleware->trustProxies(at: $trustedProxies ?: null);

        // Correlacao de logs por request_id (respeita X-Request-Id do proxy).
        $middleware->append(AttachRequestContext::class);
        $middleware->append(SecurityHeaders::class);
        $middleware->alias([
            'api-token' => AuthenticateApiToken::class,
            'api-idempotency' => ApiIdempotency::class,
            'panel.host' => EnsurePanelHost::class,
            'owner.admin-host' => EnsureOwnerAdminHost::class,
            'panel.current-host' => UseCurrentPanelHost::class,
        ]);

        // Webhook do Stripe usa assinatura HMAC propria; CSRF nao se aplica.
        $middleware->validateCsrfTokens(except: [
            'billing/webhook',
        ]);

        $middleware->redirectGuestsTo(fn () => route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
