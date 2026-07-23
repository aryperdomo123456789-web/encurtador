<?php

declare(strict_types=1);

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DomainController;
use App\Http\Controllers\LinkController;
use App\Http\Controllers\TlsAllowController;
use Illuminate\Support\Facades\Route;

$panelHost = (string) config('panel.host', '');

// Endpoint público consultado pelo proxy reverso (Caddy on_demand_tls "ask").
// Fica fora do domain guard para responder em qualquer host, já que o Caddy
// resolve via nome interno do serviço.
Route::get('/api/tls/allow', [TlsAllowController::class, 'allow'])->name('api.tls.allow');

$panelRoutes = static function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');

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

        Route::get('/links/premium', [LinkController::class, 'createPremium'])->name('links.premium');
        Route::post('/links/premium', [LinkController::class, 'storePremium'])->name('links.premium.store');

        Route::get('/domains', [DomainController::class, 'index'])->name('domains.index');
        Route::post('/domains', [DomainController::class, 'store'])->name('domains.store');
        Route::post('/domains/{customerDomain}/verify', [DomainController::class, 'verify'])->name('domains.verify');
        Route::post('/domains/{customerDomain}/tls', [DomainController::class, 'tls'])->name('domains.tls');
        Route::delete('/domains/{customerDomain}', [DomainController::class, 'destroy'])->name('domains.destroy');

        Route::get('/analytics/{shortCode}', [AnalyticsController::class, 'show'])->name('analytics.show');
    });
};

if ($panelHost !== '') {
    Route::domain($panelHost)->group($panelRoutes);
} else {
    $panelRoutes();
}
