<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

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
        if (preg_match('/\\A[a-zA-Z0-9][a-zA-Z0-9._:-]{0,127}\\z/D', $requestId) !== 1) {
            $requestId = (string) Str::uuid();
        }

        $request->attributes->set('request_id', $requestId);

        $userId = null;
        try {
            $userId = optional($request->user())->id;
        } catch (Throwable) {
            // Health e APIs sem sessão continuam operacionais.
        }

        Log::withContext([
            'request_id' => $requestId,
            'user_id' => $userId,
            'ip' => $request->ip(),
            'method' => $request->getMethod(),
            'path' => '/'.ltrim($request->path(), '/'),
        ]);

        $startedAt = microtime(true);

        /** @var Response $response */
        $response = $next($request);
        $response->headers->set('X-Request-Id', $requestId);

        Log::withContext([
            'status' => $response->getStatusCode(),
            'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
        ]);

        return $response;
    }
}
