<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

final class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_solicitacao_de_reset_responde_de_forma_neutra_e_notifica_usuario(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'cliente@empresa.com']);

        $response = $this->post(route('password.email'), [
            'email' => $user->email,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');
        Notification::assertSentTo($user, ResetPassword::class);
        $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_solicitacao_para_email_desconhecido_mantem_a_mesma_resposta(): void
    {
        Notification::fake();

        $response = $this->post(route('password.email'), [
            'email' => 'inexistente@empresa.com',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');
        Notification::assertNothingSent();
    }
}
