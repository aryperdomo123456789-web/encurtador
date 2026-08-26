<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

final class PasswordResetController extends Controller
{
    public function requestForm(): View
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $status = Password::sendResetLink(['email' => strtolower(trim((string) $data['email']))]);

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', 'Se o e-mail estiver cadastrado, enviaremos um link para redefinir a senha.');
        }

        return back()->with('status', 'Se o e-mail estiver cadastrado, enviaremos um link para redefinir a senha.');
    }

    public function resetForm(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset(
            [
                'token' => $data['token'],
                'email' => strtolower(trim((string) $data['email'])),
                'password' => $data['password'],
                'password_confirmation' => $request->input('password_confirmation'),
            ],
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', 'Senha redefinida. Você já pode entrar no painel.');
        }

        return back()->withErrors(['email' => 'Não foi possível redefinir a senha. Solicite um novo link.']);
    }
}
