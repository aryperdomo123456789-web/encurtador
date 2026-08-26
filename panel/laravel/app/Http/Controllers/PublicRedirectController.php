<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

final class PublicRedirectController extends Controller
{
    public function __invoke(Request $request, string $path): Response
    {
        $normalizedPath = ltrim($path, '/');
        $maxPathLength = max(1, (int) config('shlink.redirect_max_path_length', 160));

        if ($normalizedPath === '' || strlen($normalizedPath) > $maxPathLength || $this->isBlockedPath($normalizedPath)) {
            return response('', 404, [
                'Cache-Control' => 'public, max-age=60',
            ]);
        }

        $baseUrl = rtrim((string) config('shlink.redirect_base_url', config('shlink.base_url', env('SHLINK_BASE_URL', ''))), '/');
        if ($baseUrl === '') {
            abort(503, 'Shlink base URL nao configurada.');
        }

        $upstreamUrl = $baseUrl.'/'.$normalizedPath;
        $requestId = (string) ($request->attributes->get('request_id') ?? $request->header('X-Request-Id', ''));
        $host = (string) $request->getHost();

        try {
            $pendingRequest = Http::withHeaders([
                'Host' => $host,
                'X-Forwarded-Host' => $host,
                'X-Forwarded-Proto' => (string) $request->getScheme(),
                'X-Real-IP' => (string) $request->ip(),
                'X-Request-Id' => $requestId,
            ])
                ->connectTimeout(max(0.1, (float) config('shlink.redirect_connect_timeout', 1.0)))
                ->timeout(max(0.5, (float) config('shlink.redirect_timeout', 3.0)))
                ->withOptions(['allow_redirects' => false]);

            $response = $pendingRequest->send($request->method(), $upstreamUrl, [
                'query' => $request->query(),
            ]);
        } catch (Throwable $e) {
            Log::warning('shlink.redirect_unavailable', [
                'request_id' => $requestId !== '' ? $requestId : null,
                'host' => $host,
                'path' => $normalizedPath,
                'exception' => $e::class,
            ]);

            abort(502, 'Nao foi possivel consultar o motor Shlink.');
        }

        $headers = [];
        foreach ($response->headers() as $name => $values) {
            $headerName = strtolower((string) $name);
            if (in_array($headerName, [
                'transfer-encoding',
                'content-encoding',
                'connection',
                'keep-alive',
                'proxy-authenticate',
                'proxy-authorization',
                'te',
                'trailer',
                'upgrade',
            ], true)) {
                continue;
            }

            $headers[$name] = is_array($values) ? implode(', ', $values) : (string) $values;
        }

        return response($request->isMethod('HEAD') ? '' : $response->body(), $response->status())
            ->withHeaders($headers);
    }

    private function isBlockedPath(string $path): bool
    {
        foreach ((array) config('shlink.redirect_blocked_patterns', []) as $pattern) {
            if (! is_string($pattern) || $pattern === '') {
                continue;
            }

            $matched = @preg_match($pattern, $path);
            if ($matched === 1) {
                return true;
            }
        }

        return false;
    }
}
