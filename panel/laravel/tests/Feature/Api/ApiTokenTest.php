<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class ApiTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_rejeita_request_sem_bearer(): void
    {
        $this->getJson('/api/v1/me')
            ->assertUnauthorized()
            ->assertJson(['error' => 'unauthorized']);
    }

    public function test_api_autentica_token_por_hash_e_retorna_usuario(): void
    {
        $user = User::factory()->create();
        $token = $this->issueToken($user, ['read']);

        $this->withToken($token)
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', $user->email);

        $this->assertNotNull(ApiToken::query()->where('user_id', $user->id)->firstOrFail()->last_used_at);
    }

    public function test_api_rejeita_scope_insuficiente(): void
    {
        $token = $this->issueToken(User::factory()->create(), ['read']);

        $this->withToken($token)
            ->postJson('/api/v1/links', ['long_url' => 'https://example.com'])
            ->assertForbidden()
            ->assertJson(['error' => 'insufficient_scope']);
    }

    public function test_api_rejeita_token_expirado(): void
    {
        $user = User::factory()->create();
        $token = $this->issueToken($user, ['read'], now()->subMinute());

        $this->withToken($token)
            ->getJson('/api/v1/me')
            ->assertUnauthorized();
    }

    /** @param array<int,string> $scopes */
    private function issueToken(User $user, array $scopes, mixed $expiresAt = null): string
    {
        $plainToken = 'mlk_live_'.Str::random(48);
        ApiToken::query()->create([
            'user_id' => $user->id,
            'name' => 'Teste',
            'token_prefix' => ApiToken::prefixForPlainToken($plainToken),
            'token_hash' => ApiToken::hashPlainToken($plainToken),
            'scopes' => $scopes,
            'expires_at' => $expiresAt,
        ]);

        return $plainToken;
    }
}
