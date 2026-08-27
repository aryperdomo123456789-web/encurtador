<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\ApiIdempotency as ApiIdempotencyRecord;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ApiIdempotency
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = trim((string) $request->header('Idempotency-Key', ''));
        if (! preg_match('/^[A-Za-z0-9._:-]{1,80}$/', $key)) {
            return response()->json([
                'error' => 'idempotency_key_required',
                'message' => 'Envie um Idempotency-Key válido para requests de escrita.',
            ], 428);
        }

        $userId = (int) $request->user()->id;
        $route = (string) ($request->route()?->getName() ?? $request->path());
        $method = strtoupper($request->method());
        $requestHash = hash('sha256', (string) json_encode($request->all(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $record = ApiIdempotencyRecord::query()
            ->where('user_id', $userId)
            ->where('idempotency_key', $key)
            ->where('method', $method)
            ->where('route', $route)
            ->first();

        if ($record !== null) {
            if ($record->expires_at->isPast()) {
                $record->delete();
            } elseif (! hash_equals($record->request_hash, $requestHash)) {
                return response()->json([
                    'error' => 'idempotency_key_reused',
                    'message' => 'A chave já foi usada com outro payload.',
                ], 409);
            } elseif ($record->status_code !== null && $record->response_body !== null) {
                return response($record->response_body, $record->status_code)
                    ->header('Content-Type', 'application/json');
            } else {
                return response()->json([
                    'error' => 'request_in_progress',
                    'message' => 'Já existe uma operação em andamento para esta chave.',
                ], 409);
            }
        }

        try {
            $record = ApiIdempotencyRecord::query()->create([
                'user_id' => $userId,
                'idempotency_key' => $key,
                'method' => $method,
                'route' => $route,
                'request_hash' => $requestHash,
                'expires_at' => now()->addHours(24),
            ]);
        } catch (\Throwable $exception) {
            $record = ApiIdempotencyRecord::query()
                ->where('user_id', $userId)
                ->where('idempotency_key', $key)
                ->where('method', $method)
                ->where('route', $route)
                ->first();

            if ($record === null) {
                throw $exception;
            }

            if (! hash_equals($record->request_hash, $requestHash)) {
                return response()->json(['error' => 'idempotency_key_reused'], 409);
            }
            if ($record->status_code !== null && $record->response_body !== null) {
                return response($record->response_body, $record->status_code)
                    ->header('Content-Type', 'application/json');
            }

            return response()->json(['error' => 'request_in_progress'], 409);
        }

        $response = $next($request);
        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
            $record->forceFill([
                'status_code' => $response->getStatusCode(),
                'response_body' => $response->getContent(),
            ])->save();
        } else {
            $record->delete();
        }

        return $response;
    }
}
