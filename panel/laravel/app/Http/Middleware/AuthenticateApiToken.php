<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next, string ...$scopes): Response
    {
        $plainToken = trim((string) $request->bearerToken());
        if ($plainToken === '' || strlen($plainToken) < 32 || strlen($plainToken) > 160) {
            return $this->unauthorized();
        }

        $tokenHash = ApiToken::hashPlainToken($plainToken);
        $token = ApiToken::query()
            ->with('user')
            ->where('token_prefix', ApiToken::prefixForPlainToken($plainToken))
            ->whereNull('revoked_at')
            ->where(function ($query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->get()
            ->first(static fn (ApiToken $candidate): bool => hash_equals($candidate->token_hash, $tokenHash));
        if ($token === null || ! $token->isValid()) {
            return $this->unauthorized();
        }

        foreach ($scopes as $scope) {
            if (! $token->allows($scope)) {
                return response()->json(['error' => 'insufficient_scope'], 403);
            }
        }

        $request->setUserResolver(static fn () => $token->user);
        $request->attributes->set('api_token', $token);
        $token->forceFill(['last_used_at' => now()])->saveQuietly();

        return $next($request);
    }

    private function unauthorized(): JsonResponse
    {
        return response()
            ->json(['error' => 'unauthorized'], 401)
            ->header('WWW-Authenticate', 'Bearer');
    }
}
