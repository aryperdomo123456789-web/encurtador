<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

final class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_screen_renders_for_guest(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Criar sua conta', false);
    }

    public function test_guest_can_create_account_and_is_logged_in(): void
    {
        $response = $this->post(route('register.attempt'), [
            'name' => 'Nova Conta',
            'email' => 'nova@empresa.com',
            'password' => 'senha-super-segura',
            'password_confirmation' => 'senha-super-segura',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
        $this->assertSame('nova@empresa.com', Auth::user()?->email);

        $this->assertDatabaseHas('users', [
            'email' => 'nova@empresa.com',
        ]);
    }

    public function test_register_rejects_duplicate_email(): void
    {
        User::factory()->create([
            'email' => 'nova@empresa.com',
        ]);

        $response = $this->from(route('register'))->post(route('register.attempt'), [
            'name' => 'Nova Conta',
            'email' => 'nova@empresa.com',
            'password' => 'senha-super-segura',
            'password_confirmation' => 'senha-super-segura',
        ]);

        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
