<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\BrandingController;
use App\Http\Controllers\Admin\RichPreviewController as AdminRichPreviewController;
use App\Http\Controllers\Admin\UserAdminController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\ApiTokenController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DomainController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\LinkController;
use App\Http\Controllers\PrivacyController;
use App\Http\Controllers\PublicRedirectController;
use App\Http\Controllers\QrCodeController;
use App\Http\Controllers\RichPreviewController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\WorkspaceController;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

// Endpoints de saude ficam fora do domain guard para permitir monitoramento
// externo (uptime, orquestrador, balanceador) sem depender do PANEL_HOST.
Route::get('/tls/allow', [HealthController::class, 'tlsAllow'])
    ->withoutMiddleware(['web', StartSession::class, ShareErrorsFromSession::class])
    ->middleware('throttle:health')
    ->name('tls.allow');
Route::get('/health/release', [HealthController::class, 'release'])
    ->withoutMiddleware(['web', StartSession::class, ShareErrorsFromSession::class])
    ->middleware('throttle:health')
    ->name('health.release');
Route::get('/healthz', [HealthController::class, 'live'])
    ->withoutMiddleware(['web', StartSession::class, ShareErrorsFromSession::class])
    ->middleware('throttle:health')
    ->name('health.live');
Route::get('/health/ready', [HealthController::class, 'ready'])
    ->withoutMiddleware(['web', StartSession::class, ShareErrorsFromSession::class])
    ->middleware('throttle:health')
    ->name('health.ready');

$panelRoutes = static function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::middleware('guest')->group(function (): void {
        Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AuthController::class, 'login'])
            ->middleware('throttle:auth-login')
            ->name('login.attempt');
        Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
        Route::post('/register', [AuthController::class, 'register'])
            ->middleware('throttle:auth-register')
            ->name('register.attempt');
        Route::get('/forgot-password', [PasswordResetController::class, 'requestForm'])->name('password.request');
        Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])
            ->middleware('throttle:auth-login')
            ->name('password.email');
        Route::get('/reset-password/{token}', [PasswordResetController::class, 'resetForm'])->name('password.reset');
        Route::post('/reset-password', [PasswordResetController::class, 'reset'])
            ->middleware('throttle:auth-login')
            ->name('password.update');
    });

    Route::middleware('auth')->group(function (): void {
        Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
        Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
            ->middleware(['signed', 'throttle:auth-register'])
            ->name('verification.verify');
        Route::post('/email/verification-notification', [EmailVerificationController::class, 'send'])
            ->middleware('throttle:auth-register')
            ->name('verification.send');
    });

    $authenticatedMiddleware = ['auth'];
    if ((bool) config('panel.require_email_verification')) {
        $authenticatedMiddleware[] = 'verified';
    }

    Route::middleware(array_merge($authenticatedMiddleware, ['owner.admin-host']))->group(function (): void {
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
        Route::get('/links/{link}/qr', QrCodeController::class)->name('links.qr');
        Route::patch('/links/{link}', [LinkController::class, 'update'])->name('links.update');
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
        Route::get('/analytics/{shortCode}/export', [AnalyticsController::class, 'export'])->name('analytics.export');

        Route::get('/settings/privacy', [PrivacyController::class, 'index'])->name('privacy.index');
        Route::get('/settings/privacy/export', [PrivacyController::class, 'export'])->name('privacy.export');

        Route::get('/settings/api-tokens', [ApiTokenController::class, 'index'])->name('api-tokens.index');
        Route::post('/settings/api-tokens', [ApiTokenController::class, 'store'])->name('api-tokens.store');
        Route::delete('/settings/api-tokens/{apiToken}', [ApiTokenController::class, 'destroy'])->name('api-tokens.destroy');

        Route::get('/settings/workspaces', [WorkspaceController::class, 'index'])->name('workspaces.index');
        Route::post('/settings/workspaces', [WorkspaceController::class, 'store'])->name('workspaces.store');
        Route::post('/settings/workspaces/{workspace}/switch', [WorkspaceController::class, 'switch'])->name('workspaces.switch');
        Route::post('/settings/workspaces/{workspace}/members', [WorkspaceController::class, 'addMember'])->name('workspaces.members.add');
        Route::delete('/settings/workspaces/{workspace}/members/{member}', [WorkspaceController::class, 'removeMember'])->name('workspaces.members.remove');

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
        ->middleware('throttle:public-redirect')
        ->where('path', '.*')
        ->name('public.redirect');
};

Route::middleware(['panel.host', 'panel.current-host'])->group($panelRoutes);
