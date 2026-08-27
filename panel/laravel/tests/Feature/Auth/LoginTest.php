<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

final class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_accessing_protected_route(): void
    {
        $this->get('/links')
            ->assertRedirect(route('login'));
    }

    public function test_login_screen_renders_for_guest(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('login', false);
    }

    public function test_login_with_valid_credentials_redirects_to_dashboard(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('secret-password'),
        ]);

        $response = $this->post(route('login.attempt'), [
            'email' => $user->email,
            'password' => 'secret-password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertTrue(Auth::check());
        $this->assertSame($user->id, Auth::id());
    }

    public function test_login_with_unverified_user_redirects_to_verification_when_enabled(): void
    {
        config()->set('panel.require_email_verification', true);
        $user = User::factory()->unverified()->create([
            'password' => bcrypt('secret-password'),
        ]);

        $response = $this->post(route('login.attempt'), [
            'email' => $user->email,
            'password' => 'secret-password',
        ]);

        $response->assertRedirect(route('verification.notice'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_with_invalid_credentials_returns_back_with_error(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('secret-password'),
        ]);

        $response = $this
            ->from(route('login'))
            ->post(route('login.attempt'), [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_logout_ends_session_and_redirects_to_login(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('logout'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
