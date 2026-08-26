<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Admin\BrandingController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\RichPreviewController as AdminRichPreviewController;
use App\Http\Controllers\Admin\UserAdminController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DomainController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\LinkController;
use App\Http\Controllers\RichPreviewController;
use App\Http\Controllers\PublicRedirectController;
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
        Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
        Route::post('/register', [AuthController::class, 'register'])->name('register.attempt');
    });

    Route::middleware('auth')->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

        Route::prefix('/admin')->name('admin.')->group(function (): void {
            Route::get('/', [UserAdminController::class, 'index'])->name('dashboard');
            Route::get('/users', [UserAdminController::class, 'index'])->name('users.index');
            Route::get('/users/{user}', [UserAdminController::class, 'show'])->name('users.show');
            Route::post('/users/{user}/reset-password', [UserAdminController::class, 'resetPassword'])->name('users.reset-password');
            Route::get('/branding', [BrandingController::class, 'edit'])->name('branding.edit');
            Route::post('/branding', [BrandingController::class, 'update'])->name('branding.update');
            Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
            Route::get('/rich-previews', [AdminRichPreviewController::class, 'index'])->name('rich-previews.index');
            Route::get('/rich-previews/create', [AdminRichPreviewController::class, 'create'])->name('rich-previews.create');
            Route::post('/rich-previews', [AdminRichPreviewController::class, 'store'])->name('rich-previews.store');
            Route::get('/rich-previews/{richPreview}', [AdminRichPreviewController::class, 'edit'])->name('rich-previews.edit');
            Route::put('/rich-previews/{richPreview}', [AdminRichPreviewController::class, 'update'])->name('rich-previews.update');
            Route::post('/rich-previews/{richPreview}/duplicate', [AdminRichPreviewController::class, 'duplicate'])->name('rich-previews.duplicate');
            Route::delete('/rich-previews/{richPreview}', [AdminRichPreviewController::class, 'destroy'])->name('rich-previews.destroy');
        });

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

    Route::get('/r/{richPreview:slug}', [RichPreviewController::class, 'show'])->name('rich-previews.public');
    Route::get('/r/{richPreview:slug}/go', [RichPreviewController::class, 'go'])->name('rich-previews.go');

    // Webhook Stripe (publico, autenticado via HMAC).
    Route::post('/billing/webhook', [StripeWebhookController::class, 'handle'])->name('billing.webhook');

    // Fallback público: se a borda nao encaminhar o slug diretamente para o
    // Shlink, o Laravel repassa a requisição para o motor e devolve a resposta
    // original sem interferir nos caminhos administrativos acima.
    Route::match(['GET', 'HEAD'], '/{path}', PublicRedirectController::class)
        ->where('path', '.*')
        ->name('public.redirect');
};

if ($panelHost !== '') {
    Route::domain($panelHost)->group($panelRoutes);
} else {
    $panelRoutes();
}
