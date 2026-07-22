<?php

declare(strict_types=1);

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DomainController;
use App\Http\Controllers\LinkController;
use Illuminate\Support\Facades\Route;

$panelHost = (string) config('panel.host', '');

$panelRoutes = static function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');

    // Autenticação base do painel (sem Breeze/Fortify por enquanto).
    // O middleware `guest` evita renderizar /login para quem já está autenticado.
    Route::middleware('guest')->group(function (): void {
        Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
    });

    Route::post('/logout', [AuthController::class, 'logout'])
        ->middleware('auth')
        ->name('logout');

    Route::middleware(['auth'])->group(function (): void {
        Route::get('/links', [LinkController::class, 'index'])->name('links.index');
        Route::get('/links/create', [LinkController::class, 'create'])->name('links.create');
        Route::post('/links', [LinkController::class, 'store'])->name('links.store');

        // Fluxo premium (customSlug). Gate final no controller via isPremium().
        Route::get('/links/premium', [LinkController::class, 'createPremium'])->name('links.premium');
        Route::post('/links/premium', [LinkController::class, 'storePremium'])->name('links.premium.store');

        // Domínios próprios. Gate no controller via canUseCustomDomain().
        Route::get('/domains', [DomainController::class, 'index'])->name('domains.index');
        Route::post('/domains', [DomainController::class, 'store'])->name('domains.store');
        Route::post('/domains/{customerDomain}/verify', [DomainController::class, 'verify'])->name('domains.verify');
        Route::delete('/domains/{customerDomain}', [DomainController::class, 'destroy'])->name('domains.destroy');

        Route::get('/analytics/{shortCode}', [AnalyticsController::class, 'show'])->name('analytics.show');
    });
};

if ($panelHost !== '') {
    // Domain guard: garante que rotas do painel só respondem em PANEL_HOST,
    // evitando colisão com slugs públicos do Shlink.
    Route::domain($panelHost)->group($panelRoutes);
} else {
    // Fallback local (PANEL_HOST vazio): serve o painel em qualquer host.
    $panelRoutes();
}
