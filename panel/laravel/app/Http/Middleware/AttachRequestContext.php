<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Anexa contexto estruturado a todos os logs da requisicao e propaga
 * um request_id no header de resposta para correlacao ponta a ponta.
 *
 * Respeita X-Request-Id enviado pelo proxy reverso (Caddy/Traefik).
 */
class AttachRequestContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = (string) $request->headers->get('X-Request-Id', '');
        if ($requestId === '' || strlen($requestId) > 128) {
            $requestId = (string) Str::uuid();
        }

        $request->attributes->set('request_id', $requestId);

        Log::withContext([
            'request_id' => $requestId,
            'user_id' => $request->hasSession() ? optional($request->user())->id : null,
            'ip' => $request->ip(),
            'method' => $request->getMethod(),
            'path' => '/'.ltrim($request->path(), '/'),
        ]);

        /** @var Response $response */
        $response = $next($request);
        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }
}
