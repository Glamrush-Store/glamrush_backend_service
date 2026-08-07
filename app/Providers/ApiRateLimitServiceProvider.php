<?php

namespace App\Providers;

use App\Shared\Security\ApiRateLimitKey;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

final class ApiRateLimitServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request): Limit {
            $routeMiddleware = $request->route()?->gatherMiddleware() ?? [];
            $attempts = in_array('throttle:catalog', $routeMiddleware, true)
                ? config('api_rate_limits.catalog_per_minute')
                : config('api_rate_limits.general_per_minute');

            return $this->perMinute(
                $attempts,
                'requester:'.ApiRateLimitKey::customer($request),
            );
        });

        RateLimiter::for('catalog', fn (Request $request): Limit => $this->perMinute(
            config('api_rate_limits.catalog_per_minute'),
            'ip:'.ApiRateLimitKey::ip($request),
        ));

        RateLimiter::for('login', fn (Request $request): array => [
            $this->perMinute(
                config('api_rate_limits.login_per_minute'),
                'email:'.ApiRateLimitKey::email($request->input('email')),
            ),
            $this->perMinute(
                config('api_rate_limits.login_per_minute'),
                'ip:'.ApiRateLimitKey::ip($request),
            ),
        ]);

        RateLimiter::for('onboarding', fn (Request $request): Limit => $this->perMinute(
            config('api_rate_limits.onboarding_per_minute'),
            'ip:'.ApiRateLimitKey::ip($request),
        ));

        RateLimiter::for('password-forgot', fn (Request $request): array => [
            $this->perHour(
                config('api_rate_limits.password_forgot_per_hour_per_email'),
                'email:'.ApiRateLimitKey::email($request->input('email')),
            ),
            $this->perHour(
                config('api_rate_limits.password_forgot_per_hour_per_ip'),
                'ip:'.ApiRateLimitKey::ip($request),
            ),
        ]);

        RateLimiter::for('password-verify', function (Request $request): Limit {
            $generation = Cache::get(
                ApiRateLimitKey::passwordResetGenerationCacheKey($request->input('email')),
                'unissued',
            );

            return $this->perHour(
                config('api_rate_limits.password_verify_per_request'),
                'request:'.hash('sha256', ApiRateLimitKey::email($request->input('email')).'|'.$generation),
            );
        });

        RateLimiter::for('password-reset', fn (Request $request): array => [
            $this->perHour(
                config('api_rate_limits.password_reset_per_hour'),
                'email:'.ApiRateLimitKey::email($request->input('email')),
            ),
            $this->perHour(
                config('api_rate_limits.password_reset_per_hour'),
                'ip:'.ApiRateLimitKey::ip($request),
            ),
        ]);

        RateLimiter::for('cart-mutation', fn (Request $request): Limit => $this->perMinute(
            config('api_rate_limits.cart_mutations_per_minute'),
            'customer:'.ApiRateLimitKey::customer($request),
        ));

        RateLimiter::for('checkout-payment', fn (Request $request): Limit => $this->perMinute(
            config('api_rate_limits.checkout_payment_per_minute'),
            'customer:'.ApiRateLimitKey::customer($request),
        ));

        RateLimiter::for('payment-verify', fn (Request $request): array => [
            $this->perMinute(
                config('api_rate_limits.payment_verification_per_minute'),
                'ip:'.ApiRateLimitKey::ip($request),
            ),
            $this->perMinute(
                config('api_rate_limits.payment_verification_per_minute'),
                'reference:'.hash('sha256', implode('|', [
                    (string) $request->input('provider'),
                    (string) $request->input('transaction_id'),
                ])),
            ),
        ]);

        RateLimiter::for('payment-webhook', fn (Request $request): array => [
            $this->perMinute(
                config('api_rate_limits.webhook_per_minute_per_ip'),
                'provider-ip:'.hash('sha256', $request->route('provider').'|'.$request->ip()),
            ),
            $this->perMinute(
                config('api_rate_limits.webhook_per_minute_per_provider'),
                'provider:'.hash('sha256', (string) $request->route('provider')),
            ),
        ]);

        RateLimiter::for('newsletter-subscribe', fn (Request $request): array => [
            $this->perHour(
                config('api_rate_limits.newsletter_subscribe_per_hour_per_email'),
                'email:'.ApiRateLimitKey::email($request->input('email')),
            ),
            $this->perHour(
                config('api_rate_limits.newsletter_subscribe_per_hour_per_ip'),
                'ip:'.ApiRateLimitKey::ip($request),
            ),
        ]);

        RateLimiter::for('newsletter-action', fn (Request $request): Limit => $this->perMinute(
            config('api_rate_limits.newsletter_action_per_minute'),
            'ip:'.ApiRateLimitKey::ip($request),
        ));
    }

    private function perMinute(mixed $attempts, string $key): Limit
    {
        return $this->withJsonResponse(Limit::perMinute(max(1, (int) $attempts))->by($key));
    }

    private function perHour(mixed $attempts, string $key): Limit
    {
        return $this->withJsonResponse(Limit::perHour(max(1, (int) $attempts))->by($key));
    }

    private function withJsonResponse(Limit $limit): Limit
    {
        return $limit->response(
            fn (Request $request, array $headers): JsonResponse => response()->json([
                'success' => false,
                'message' => 'Too many requests. Please try again later.',
                'data' => null,
                'errors' => [],
            ], 429, $headers),
        );
    }
}
