<?php

namespace App\Providers;

use App\Domain\Setting\Services\RuntimeSettingService;
use App\Infrastructure\Settings\GoogleCredentialsConfigurator;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;

final class RuntimeSettingsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(RuntimeSettingService::class);
        $this->app->singleton(GoogleCredentialsConfigurator::class);
    }

    public function boot(RuntimeSettingService $settings, GoogleCredentialsConfigurator $googleCredentials): void
    {
        $settings->applyToConfiguration();

        Queue::before(function () use ($settings, $googleCredentials): void {
            $settings->applyToConfiguration();
            $googleCredentials->apply();
        });
    }
}
