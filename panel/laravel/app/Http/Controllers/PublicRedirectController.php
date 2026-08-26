<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

final class PublicRedirectController extends Controller
{
    public function __invoke(Request $request, string $path): Response
    {
        $baseUrl = rtrim((string) config('shlink.base_url', env('SHLINK_BASE_URL', '')), '/');
        if ($baseUrl === '') {
            abort(503, 'Shlink base URL nao configurada.');
        }

        $upstreamUrl = $baseUrl . '/' . ltrim($path, '/');

        try {
            $response = Http::withHeaders([
                'Host' => (string) $request->getHost(),
                'X-Forwarded-Host' => (string) $request->getHost(),
                'X-Forwarded-Proto' => (string) $request->getScheme(),
                'X-Real-IP' => (string) $request->ip(),
                'X-Request-Id' => (string) ($request->attributes->get('request_id') ?? ''),
            ])
                ->connectTimeout(3)
                ->timeout((int) config('shlink.timeout', 10))
                ->withOptions(['allow_redirects' => false])
                ->send($request->method(), $upstreamUrl, [
                    'query' => $request->query(),
                    'body' => $request->getContent(),
                ]);
        } catch (Throwable $e) {
            report($e);
            abort(502, 'Nao foi possivel consultar o motor Shlink.');
        }

        $headers = [];
        foreach ($response->headers() as $name => $values) {
            $headerName = strtolower((string) $name);
            if (in_array($headerName, ['transfer-encoding', 'content-encoding', 'connection'], true)) {
                continue;
            }

            $headers[$name] = is_array($values) ? implode(', ', $values) : (string) $values;
        }

        return response($response->body(), $response->status())->withHeaders($headers);
    }
}
