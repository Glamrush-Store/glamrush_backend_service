<?php

namespace App\Presentation\Http\Middleware;

use App\Domain\Setting\Services\RuntimeSettingService;
use App\Infrastructure\Settings\CloudflareR2Configurator;
use App\Infrastructure\Settings\GoogleCredentialsConfigurator;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ApplyRuntimeSettings
{
    public function __construct(
        private readonly RuntimeSettingService $settings,
        private readonly GoogleCredentialsConfigurator $googleCredentials,
        private readonly CloudflareR2Configurator $cloudflareR2,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->settings->applyToConfiguration();
        $this->googleCredentials->apply();
        $this->cloudflareR2->apply();

        return $next($request);
    }
}
