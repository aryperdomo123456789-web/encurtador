<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DomainController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\LinkController;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;

$panelHost = (string) config('panel.host', '');

// Endpoints de saude ficam fora do domain guard para permitir monitoramento
// externo (uptime, orquestrador, balanceador) sem depender do PANEL_HOST.
Route::get('/tls/allow', [HealthController::class, 'tlsAllow'])->name('tls.allow');
Route::get('/healthz', [HealthController::class, 'live'])->name('health.live');
Route::get('/health/ready', [HealthController::class, 'ready'])->name('health.ready');

$panelRoutes = static function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::middleware('guest')->group(function (): void {
        Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
    });

    Route::middleware('auth')->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

        Route::get('/links', [LinkController::class, 'index'])->name('links.index');
        Route::delete('/links/{link}', [LinkController::class, 'destroy'])->name('links.destroy');

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

        // Billing (Stripe). Estado real e atualizado pelo webhook.
        Route::get('/billing', [BillingController::class, 'index'])->name('billing.index');
        Route::post('/billing/checkout', [BillingController::class, 'checkout'])->name('billing.checkout');
        Route::post('/billing/portal', [BillingController::class, 'portal'])->name('billing.portal');
        Route::get('/billing/success', [BillingController::class, 'success'])->name('billing.success');
        Route::get('/billing/cancel', [BillingController::class, 'cancel'])->name('billing.cancel');
    });

    // Webhook Stripe (publico, autenticado via HMAC).
    Route::post('/billing/webhook', [StripeWebhookController::class, 'handle'])->name('billing.webhook');
};

if ($panelHost !== '') {
    Route::domain($panelHost)->group($panelRoutes);
} else {
    $panelRoutes();
}
