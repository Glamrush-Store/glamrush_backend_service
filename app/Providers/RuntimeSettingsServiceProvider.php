<?php

namespace App\Providers;

use App\Domain\Setting\Services\RuntimeSettingService;
use App\Infrastructure\Settings\CloudflareR2Configurator;
use App\Infrastructure\Settings\GoogleCredentialsConfigurator;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;

final class RuntimeSettingsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(RuntimeSettingService::class);
        $this->app->singleton(GoogleCredentialsConfigurator::class);
        $this->app->singleton(CloudflareR2Configurator::class);
    }

    public function boot(
        RuntimeSettingService $settings,
        GoogleCredentialsConfigurator $googleCredentials,
        CloudflareR2Configurator $cloudflareR2,
    ): void {
        $settings->applyToConfiguration();
        $cloudflareR2->apply();

        Queue::before(function () use ($settings, $googleCredentials, $cloudflareR2): void {
            $settings->applyToConfiguration();
            $googleCredentials->apply();
            $cloudflareR2->apply();
        });
    }
}
