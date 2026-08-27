<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Http\Middleware\ApiIdempotency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

final class ApiIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_escrita_exige_idempotency_key(): void
    {
        $request = $this->requestWithUser(User::factory()->create(), []);

        $response = app(ApiIdempotency::class)->handle($request, fn () => response()->json(['ok' => true], 201));

        $this->assertSame(428, $response->getStatusCode());
    }

    public function test_replay_de_mesma_chave_nao_executa_novamente(): void
    {
        $user = User::factory()->create();
        $payload = ['long_url' => 'https://example.com/oferta'];
        $calls = 0;
        $next = function () use (&$calls): Response {
            $calls++;

            return response()->json(['data' => ['created' => true]], 201);
        };

        $first = $this->requestWithUser($user, $payload);
        $first->headers->set('Idempotency-Key', 'campaign-001');
        app(ApiIdempotency::class)->handle($first, $next);

        $second = $this->requestWithUser($user, $payload);
        $second->headers->set('Idempotency-Key', 'campaign-001');
        $response = app(ApiIdempotency::class)->handle($second, $next);

        $this->assertSame(1, $calls);
        $this->assertSame(201, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString('{"data":{"created":true}}', (string) $response->getContent());
    }

    public function test_mesma_chave_com_payload_diferente_e_rejeitada(): void
    {
        $user = User::factory()->create();
        $middleware = app(ApiIdempotency::class);
        $first = $this->requestWithUser($user, ['long_url' => 'https://example.com/a']);
        $first->headers->set('Idempotency-Key', 'campaign-002');
        $middleware->handle($first, fn () => response()->json(['ok' => true], 201));

        $second = $this->requestWithUser($user, ['long_url' => 'https://example.com/b']);
        $second->headers->set('Idempotency-Key', 'campaign-002');
        $response = $middleware->handle($second, fn () => response()->json(['ok' => true], 201));

        $this->assertSame(409, $response->getStatusCode());
    }

    /** @param array<string,mixed> $payload */
    private function requestWithUser(User $user, array $payload): Request
    {
        $request = Request::create('/api/v1/links', 'POST', $payload);
        $request->setUserResolver(static fn () => $user);

        return $request;
    }
}
