<?php

namespace App\Providers;

use App\Contracts\FreeLinkQuotaRepository;
use App\Models\BrandingSetting;
use App\Repositories\EloquentFreeLinkQuotaRepository;
use App\Support\Domains\DomainDnsResolver;
use App\Support\Domains\SystemDnsResolver;
use App\Support\Shlink\AnalyticsService;
use App\Support\Shlink\DomainService;
use App\Support\Shlink\LinkProvisioner;
use App\Support\Shlink\ShlinkClient;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(FreeLinkQuotaRepository::class, EloquentFreeLinkQuotaRepository::class);
        $this->app->bind(DomainDnsResolver::class, SystemDnsResolver::class);

        $this->app->singleton(ShlinkClient::class, function (): ShlinkClient {
            return new ShlinkClient(
                baseUrl: (string) config('shlink.base_url', 'https://api-shlink.vr766.com'),
                apiKey: (string) config('shlink.api_key', ''),
                apiVersion: (int) config('shlink.api_version', 3),
                timeoutSeconds: (int) config('shlink.timeout', 20),
            );
        });

        $this->app->singleton(DomainService::class, fn ($app) => new DomainService($app->make(ShlinkClient::class)));
        $this->app->singleton(AnalyticsService::class, fn ($app) => new AnalyticsService($app->make(ShlinkClient::class)));

        $this->app->singleton(LinkProvisioner::class, function ($app): LinkProvisioner {
            return new LinkProvisioner(
                client: $app->make(ShlinkClient::class),
                quotaRepository: $app->make(FreeLinkQuotaRepository::class),
                freeMonthlyLimit: (int) config('shlink.free_monthly_limit', 5),
                domainService: $app->make(DomainService::class),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('public-redirect', static function (Request $request): Limit {
            $perMinute = max(1, (int) config('shlink.redirect_rate_limit', 120));
            $key = implode('|', [
                $request->getHost(),
                $request->ip() ?: 'unknown',
            ]);

            return Limit::perMinute($perMinute)->by($key);
        });

        View::composer('*', function ($view): void {
            $view->with('branding', BrandingSetting::current());
        });
    }
}
