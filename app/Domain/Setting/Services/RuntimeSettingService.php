<?php

namespace App\Domain\Setting\Services;

use App\Infrastructure\Persistence\Eloquent\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class RuntimeSettingService
{
    /** @var array<string, mixed> */
    private array $fallbacks = [];

    /** @var array<string, true> */
    private array $appliedConfigKeys = [];

    public const CACHE_KEY = 'runtime-settings:configured:v1';

    public const CATEGORIES = [
        'api-rate-limiting',
        'payments',
        'media-storage',
        'order-notification-mails',
    ];

    public function __construct()
    {
        foreach (self::CONFIG_MAP as $configKey) {
            $this->fallbacks[$configKey] = config($configKey);
        }
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        try {
            return Cache::remember(self::CACHE_KEY, now()->addMinute(), function (): array {
                if (! Schema::hasTable('settings') || ! Schema::hasTable('setting_categories')) {
                    return [];
                }

                return Setting::query()
                    ->select(['id', 'setting_category_id', 'key', 'value', 'value_type'])
                    ->where('is_active', true)
                    ->whereHas('category', fn ($query) => $query
                        ->where('is_active', true)
                        ->where(function ($category): void {
                            $category->whereIn('slug', self::CATEGORIES)
                                ->orWhereIn('name', ['API_RATE_LIMITING', 'PAYMENTS', 'MEDIA STORAGE']);
                        }))
                    ->get()
                    ->mapWithKeys(fn (Setting $setting): array => [strtoupper($setting->key) => $setting->decodedValue()])
                    ->all();
            });
        } catch (Throwable) {
            return [];
        }
    }

    public function applyToConfiguration(): void
    {
        $settings = $this->all();
        $nextAppliedConfigKeys = [];

        foreach ($this->appliedConfigKeys as $configKey => $_) {
            $settingKey = array_search($configKey, self::CONFIG_MAP, true);

            if ($settingKey === false || ! array_key_exists($settingKey, $settings)) {
                config()->set($configKey, $this->fallbacks[$configKey]);
            }
        }

        foreach ($settings as $key => $value) {
            if ($value === null || ! array_key_exists($key, self::CONFIG_MAP)) {
                continue;
            }

            $configKey = self::CONFIG_MAP[$key];
            $current = config($configKey);
            config()->set($configKey, $this->castLike($value, $current));
            $nextAppliedConfigKeys[$configKey] = true;
        }

        $this->appliedConfigKeys = $nextAppliedConfigKeys;
    }

    public function value(string $key, mixed $fallback = null): mixed
    {
        return $this->all()[strtoupper($key)] ?? $fallback;
    }

    public function forget(): void
    {
        try {
            Cache::forget(self::CACHE_KEY);
        } catch (Throwable) {
            // Database-backed settings remain optional during bootstrap and maintenance.
        }
    }

    private function castLike(mixed $value, mixed $current): mixed
    {
        return match (true) {
            is_bool($current) => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $current,
            is_int($current) => (int) $value,
            is_float($current) => (float) $value,
            is_string($current) => (string) $value,
            default => $value,
        };
    }

    private const CONFIG_MAP = [
        'API_RATE_LIMIT_GENERAL_PER_MINUTE' => 'api_rate_limits.general_per_minute',
        'API_RATE_LIMIT_CATALOG_PER_MINUTE' => 'api_rate_limits.catalog_per_minute',
        'API_RATE_LIMIT_LOGIN_PER_MINUTE' => 'api_rate_limits.login_per_minute',
        'API_RATE_LIMIT_ONBOARDING_PER_MINUTE' => 'api_rate_limits.onboarding_per_minute',
        'API_RATE_LIMIT_PASSWORD_FORGOT_EMAIL_PER_HOUR' => 'api_rate_limits.password_forgot_per_hour_per_email',
        'API_RATE_LIMIT_PASSWORD_FORGOT_IP_PER_HOUR' => 'api_rate_limits.password_forgot_per_hour_per_ip',
        'API_RATE_LIMIT_PASSWORD_VERIFY_PER_REQUEST' => 'api_rate_limits.password_verify_per_request',
        'API_RATE_LIMIT_PASSWORD_RESET_PER_HOUR' => 'api_rate_limits.password_reset_per_hour',
        'API_RATE_LIMIT_CART_MUTATIONS_PER_MINUTE' => 'api_rate_limits.cart_mutations_per_minute',
        'API_RATE_LIMIT_CHECKOUT_PAYMENT_PER_MINUTE' => 'api_rate_limits.checkout_payment_per_minute',
        'API_RATE_LIMIT_PAYMENT_VERIFY_PER_MINUTE' => 'api_rate_limits.payment_verification_per_minute',
        'API_RATE_LIMIT_WEBHOOK_IP_PER_MINUTE' => 'api_rate_limits.webhook_per_minute_per_ip',
        'API_RATE_LIMIT_WEBHOOK_PROVIDER_PER_MINUTE' => 'api_rate_limits.webhook_per_minute_per_provider',
        'API_RATE_LIMIT_NEWSLETTER_EMAIL_PER_HOUR' => 'api_rate_limits.newsletter_subscribe_per_hour_per_email',
        'API_RATE_LIMIT_NEWSLETTER_IP_PER_HOUR' => 'api_rate_limits.newsletter_subscribe_per_hour_per_ip',
        'API_RATE_LIMIT_NEWSLETTER_ACTION_PER_MINUTE' => 'api_rate_limits.newsletter_action_per_minute',
        'API_RATE_LIMIT_CONTACT_SUBMISSIONS_PER_MINUTE' => 'api_rate_limits.contact_submissions_per_minute',
        'STOREFRONT_HOMEPAGE_CACHE_TTL' => 'storefront.homepage.cache_ttl',
        'STOREFRONT_CONTEXT_CACHE_TTL' => 'api_cache.storefront_context_ttl',
        'PAYMENT_METHODS_CACHE_TTL' => 'api_cache.payment_methods_ttl',
        'SHIPPING_CACHE_TTL' => 'api_cache.shipping_ttl',
        'PUBLIC_HTTP_CACHE_MAX_AGE' => 'api_cache.http.max_age',
        'PUBLIC_HTTP_CACHE_SHARED_MAX_AGE' => 'api_cache.http.shared_max_age',
        'PUBLIC_HTTP_CACHE_STALE_WHILE_REVALIDATE' => 'api_cache.http.stale_while_revalidate',
        'IDEMPOTENCY_LOCK_SECONDS' => 'idempotency.lock_seconds',
        'IDEMPOTENCY_WAIT_SECONDS' => 'idempotency.wait_seconds',
        'STOREFRONT_HOMEPAGE_MAX_ITEMS' => 'storefront.homepage.max_item_limit',
        'PAYSTACK_PUBLIC_KEY' => 'services.paystack.public_key',
        'PAYSTACK_SECRET_KEY' => 'services.paystack.secret_key',
        'PAYSTACK_CALLBACK_URL' => 'services.paystack.callback_url',
        'FLUTTERWAVE_PUBLIC_KEY' => 'services.flutterwave.public_key',
        'FLUTTERWAVE_SECRET_KEY' => 'services.flutterwave.secret_key',
        'FLUTTERWAVE_SECRET_HASH' => 'services.flutterwave.secret_hash',
        'FLUTTERWAVE_CALLBACK_URL' => 'services.flutterwave.callback_url',
        'FLUTTERWAVE_BASE_URL' => 'services.flutterwave.base_url',
        'GCP_PROJECT_ID' => 'filesystems.disks.gcs.project_id',
        'GCP_BUCKET' => 'filesystems.disks.gcs.bucket',
        'GOOGLE_APPLICATION_CREDENTIALS' => 'filesystems.disks.gcs.key_file',
        'GOOGLE_APPLICATION_CREDENTIALS_BASE64' => 'filesystems.disks.gcs.key_file_base64',
        'VISIBILITY' => 'filesystems.disks.gcs.visibility',
        'USE_GCP_KEY_FILE' => 'filesystems.disks.gcs.use_key_file',
        'NEW_ORDER_EMAILS' => 'services.notifications.new_order_emails',
        'PAYMENT_FAILED_EMAILS' => 'services.notifications.payment_failed_emails',
        'ABANDONED_CART_EMAILS' => 'services.notifications.abandoned_cart_emails',
    ];
}

