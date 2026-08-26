<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ApiTokenManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_pode_criar_token_e_recebe_o_segredo_uma_vez(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('api-tokens.store'), [
            'name' => 'Automação CRM',
            'scopes' => ['read', 'analytics'],
            'expires_in_days' => 90,
        ]);

        $response->assertOk()
            ->assertViewIs('api-tokens.index')
            ->assertViewHas('newToken');
        $this->assertDatabaseHas('api_tokens', [
            'user_id' => $user->id,
            'name' => 'Automação CRM',
            'token_prefix' => ApiToken::query()->firstOrFail()->token_prefix,
        ]);
    }

    public function test_usuario_pode_criar_token_com_scope_de_eventos(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('api-tokens.store'), [
            'name' => 'Conversões',
            'scopes' => ['events'],
            'expires_in_days' => 90,
        ])->assertOk();

        $this->assertDatabaseHas('api_tokens', [
            'user_id' => $user->id,
            'name' => 'Conversões',
            'scopes' => json_encode(['events']),
        ]);
    }

    public function test_usuario_pode_revogar_seu_token(): void
    {
        $user = User::factory()->create();
        $token = ApiToken::query()->create([
            'user_id' => $user->id,
            'name' => 'Revogar',
            'token_prefix' => '1234567890abcdef',
            'token_hash' => hash('sha256', 'mlk_live_token-revogar'),
            'scopes' => ['read'],
        ]);

        $this->actingAs($user)
            ->delete(route('api-tokens.destroy', $token))
            ->assertRedirect();

        $this->assertNotNull($token->fresh()->revoked_at);
    }

    public function test_usuario_nao_pode_revogar_token_de_outro(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $token = ApiToken::query()->create([
            'user_id' => $owner->id,
            'name' => 'Protegido',
            'token_prefix' => 'fedcba0987654321',
            'token_hash' => hash('sha256', 'mlk_live_token-protegido'),
            'scopes' => ['read'],
        ]);

        $this->actingAs($other)
            ->delete(route('api-tokens.destroy', $token))
            ->assertNotFound();

        $this->assertNull($token->fresh()->revoked_at);
    }
}
