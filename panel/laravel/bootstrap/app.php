<?php

use App\Http\Middleware\AttachRequestContext;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*');

        // Correlacao de logs por request_id (respeita X-Request-Id do proxy).
        $middleware->append(AttachRequestContext::class);

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
