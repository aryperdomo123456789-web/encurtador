<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Autenticação mínima do painel administrativo.
 *
 * Implementação intencionalmente simples (sem Breeze/Fortify) para cobrir o
 * P0 do checklist. Um starter kit pode substituir esta base depois.
 */
final class AuthController extends Controller
{
    public function showLogin(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->intended(route('dashboard'));
        }

        return view('auth.login');
    }

    public function showRegister(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->intended(route('dashboard'));
        }

        return view('auth.register');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        if (! Auth::attempt($credentials, $remember)) {
            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors(['email' => __('auth.failed')]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::query()->create([
            'name' => trim((string) $data['name']),
            'email' => strtolower(trim((string) $data['email'])),
            'password' => $data['password'],
        ]);

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('status', 'Conta criada com sucesso. Bem-vindo ao painel.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
