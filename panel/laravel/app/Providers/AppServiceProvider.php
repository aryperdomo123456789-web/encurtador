<?php

namespace App\Providers;

use App\Contracts\FreeLinkQuotaRepository;
use App\Repositories\EloquentFreeLinkQuotaRepository;
use App\Support\Shlink\AnalyticsService;
use App\Support\Shlink\DomainService;
use App\Support\Shlink\LinkProvisioner;
use App\Support\Shlink\ShlinkClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(FreeLinkQuotaRepository::class, EloquentFreeLinkQuotaRepository::class);

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
        //
    }
}
