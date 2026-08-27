<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

final class UseCurrentPanelHost
{
    public function handle(Request $request, Closure $next): Response
    {
        // O aaPanel termina TLS antes do container e encaminha o protocolo.
        // O vhost sobrescreve este header, evitando links HTTP no painel.
        $forwardedScheme = strtolower(trim((string) $request->header('X-Forwarded-Proto', '')));
        $scheme = in_array($forwardedScheme, ['http', 'https'], true)
            ? $forwardedScheme
            : $request->getScheme();

        URL::forceRootUrl($scheme.'://'.$request->getHttpHost());
        URL::forceScheme($scheme);

        return $next($request);
    }
}
