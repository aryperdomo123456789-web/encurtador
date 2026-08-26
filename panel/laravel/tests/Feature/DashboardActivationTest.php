<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DashboardActivationTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_account_sees_activation_path_on_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Coloque sua primeira campanha no ar')
            ->assertSee('Crie seu primeiro link')
            ->assertSee('Marque uma campanha')
            ->assertSee('Conecte sua marca')
            ->assertSee('Automatize com a API')
            ->assertSee('0/4');
    }

    public function test_fully_activated_account_does_not_see_incomplete_checklist(): void
    {
        $user = User::factory()->create();
        $freePlan = Plan::query()->where('is_free', true)->firstOrFail();
        $user->shortLinks()->create([
            'user_id' => $user->id,
            'workspace_id' => null,
            'plan_id' => $freePlan->id,
            'long_url' => 'https://example.com/campaign',
            'domain' => 'go.example.com',
            'short_code' => 'campaign',
            'shlink_short_url' => 'https://go.example.com/campaign',
            'status' => 'active',
            'is_free_link' => true,
            'utm_source' => 'newsletter',
            'utm_campaign' => 'launch',
        ]);
        $user->customerDomains()->create([
            'domain' => 'go.example.com',
            'status' => 'active',
            'dns_verified_at' => now(),
            'tls_status' => 'active',
        ]);
        $user->apiTokens()->create([
            'name' => 'Automation',
            'token_prefix' => 'testtoken',
            'token_hash' => hash('sha256', 'test-token'),
            'scopes' => ['read', 'write', 'analytics', 'events'],
            'expires_at' => now()->addYear(),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Coloque sua primeira campanha no ar');
    }
}
