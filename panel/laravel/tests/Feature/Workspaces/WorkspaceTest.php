<?php

declare(strict_types=1);

namespace Tests\Feature\Workspaces;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class WorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_premium_pode_criar_workspace_e_vira_owner(): void
    {
        $user = $this->premiumUser();

        $this->actingAs($user)
            ->post(route('workspaces.store'), ['name' => 'Agência Aurora'])
            ->assertRedirect();

        $workspace = Workspace::query()->where('owner_user_id', $user->id)->firstOrFail();
        $this->assertSame('active', $workspace->status);
        $this->assertDatabaseHas('workspace_members', [
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => 'owner',
        ]);
    }

    public function test_membro_pode_ser_adicionado_com_papel_e_nao_pode_remover_owner(): void
    {
        $owner = $this->premiumUser();
        $workspace = Workspace::query()->create([
            'owner_user_id' => $owner->id,
            'name' => 'Operação',
            'slug' => 'operacao-teste',
            'status' => 'active',
        ]);
        $workspace->members()->attach($owner->id, ['role' => 'owner']);
        $member = User::factory()->create(['email' => 'editor@empresa.com']);

        $this->actingAs($owner)
            ->post(route('workspaces.members.add', $workspace), [
                'email' => $member->email,
                'role' => 'member',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('workspace_members', [
            'workspace_id' => $workspace->id,
            'user_id' => $member->id,
            'role' => 'member',
        ]);

        $this->actingAs($member)
            ->delete(route('workspaces.members.remove', [$workspace, $owner]))
            ->assertForbidden();
    }

    public function test_usuario_free_nao_pode_criar_workspace(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('workspaces.store'), ['name' => 'Bloqueado'])
            ->assertForbidden();
    }

    private function premiumUser(): User
    {
        $user = User::factory()->create();
        $plan = Plan::query()->updateOrCreate(['code' => 'premium'], [
            'name' => 'Premium',
            'description' => 'Premium',
            'is_free' => false,
            'monthly_short_url_limit' => null,
            'allow_custom_slug' => true,
            'allow_custom_domain' => true,
            'allow_custom_expiration' => true,
            'allow_lifetime_links' => true,
            'is_active' => true,
        ]);
        Subscription::query()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'provider' => 'manual',
            'status' => 'active',
        ]);

        return $user;
    }
}
