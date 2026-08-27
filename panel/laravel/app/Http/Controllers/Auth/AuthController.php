<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
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
            return redirect()->intended($this->isAdminHost($request) ? route('admin.dashboard') : route('dashboard'));
        }

        return view($this->isAdminHost($request) ? 'auth.admin-login' : 'auth.login');
    }

    public function showRegister(Request $request): View|RedirectResponse
    {
        if ($this->isAdminHost($request)) {
            return redirect()->route('login')->withErrors([
                'email' => 'O cadastro de usuários acontece no painel público.',
            ]);
        }

        if (Auth::check()) {
            return redirect()->intended(route('dashboard'));
        }

        return view('auth.register');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        if (! Auth::attempt($credentials, $remember)) {
            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors(['email' => __('auth.failed')]);
        }

        $request->session()->regenerate();

        if ($this->isAdminHost($request) && ! $request->user()->isOwner()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors(['email' => 'Este acesso é exclusivo do proprietário.']);
        }

        if ((bool) config('panel.require_email_verification') && ! $request->user()->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        return redirect()->intended($this->isAdminHost($request) ? route('admin.dashboard') : route('dashboard'));
    }

    public function register(Request $request): RedirectResponse
    {
        abort_if($this->isAdminHost($request), 404);

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

        $workspace = Workspace::query()->create([
            'owner_user_id' => $user->id,
            'name' => $user->name.' — MElink',
            'slug' => 'workspace-'.$user->id.'-'.Str::lower(Str::random(6)),
            'status' => 'active',
        ]);
        $workspace->members()->attach($user->id, ['role' => 'owner']);

        Auth::login($user);

        $request->session()->regenerate();
        $request->session()->put('workspace_id', $workspace->id);

        if ((bool) config('panel.require_email_verification')) {
            $user->sendEmailVerificationNotification();

            return redirect()->route('verification.notice')
                ->with('status', 'Conta criada. Confirme seu e-mail para liberar o painel.');
        }

        return redirect()->route('dashboard')->with('status', 'Conta criada com sucesso. Bem-vindo ao painel.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function isAdminHost(Request $request): bool
    {
        $adminHost = (string) config('panel.admin_host', '');

        return $adminHost !== '' && $request->getHost() === $adminHost;
    }
}
