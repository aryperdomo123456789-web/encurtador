<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class UserAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_open_user_admin_dashboard(): void
    {
        $owner = User::factory()->create([
            'email' => 'mago@dono.com',
            'role' => 'owner',
        ]);

        $this->actingAs($owner)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Usuários do painel', false);
    }

    public function test_common_user_is_forbidden_from_admin_area(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_owner_can_reset_common_user_password(): void
    {
        $owner = User::factory()->create([
            'email' => 'mago@dono.com',
            'role' => 'owner',
        ]);

        $user = User::factory()->create([
            'email' => 'cliente@empresa.com',
            'password' => 'senha-original',
            'role' => 'user',
        ]);

        $this->actingAs($owner)
            ->post(route('admin.users.reset-password', $user))
            ->assertRedirect(route('admin.users.show', $user));

        $fresh = $user->fresh();
        $this->assertNotNull($fresh);
        $this->assertFalse(Hash::check('senha-original', $fresh->password));
        $this->assertTrue(Hash::check('senha-original', $fresh->password) === false);
    }

    public function test_owner_cannot_reset_owner_password_through_admin(): void
    {
        $owner = User::factory()->create([
            'email' => 'mago@dono.com',
            'role' => 'owner',
        ]);

        $this->actingAs($owner)
            ->post(route('admin.users.reset-password', $owner))
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'email' => 'mago@dono.com',
            'role' => 'owner',
        ]);
    }
}
