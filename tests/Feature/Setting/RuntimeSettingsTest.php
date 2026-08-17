<?php

use App\Domain\Setting\Services\NotificationRecipientResolver;
use App\Domain\Setting\Services\RuntimeSettingService;
use App\Infrastructure\Persistence\Eloquent\Models\Setting;
use App\Infrastructure\Persistence\Eloquent\Models\SettingCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(fn () => Cache::flush());

it('overrides environment-backed configuration with active typed settings', function () {
    config()->set([
        'api_rate_limits.general_per_minute' => 120,
        'services.paystack.secret_key' => 'environment-secret',
        'filesystems.disks.gcs.use_key_file' => true,
        'filesystems.disks.r2.secret' => 'environment-r2-secret',
        'filesystems.disks.r2.use_path_style_endpoint' => false,
    ]);

    $rateLimits = runtimeSettingCategory('API_RATE_LIMITING', 'api-rate-limiting');
    $payments = runtimeSettingCategory('PAYMENTS', 'payments');
    $media = runtimeSettingCategory('MEDIA STORAGE', 'media-storage');

    runtimeSetting($rateLimits, 'API_RATE_LIMIT_GENERAL_PER_MINUTE', 'integer', 45);
    runtimeSetting($payments, 'PAYSTACK_SECRET_KEY', 'string', 'database-secret');
    runtimeSetting($media, 'USE_GCP_KEY_FILE', 'boolean', false);
    runtimeSetting($media, 'R2_SECRET_ACCESS_KEY', 'string', 'database-r2-secret');
    runtimeSetting($media, 'R2_USE_PATH_STYLE_ENDPOINT', 'boolean', true);

    $settings = new RuntimeSettingService;
    $settings->applyToConfiguration();

    expect(config('api_rate_limits.general_per_minute'))->toBe(45)
        ->and(config('services.paystack.secret_key'))->toBe('database-secret')
        ->and(config('filesystems.disks.gcs.use_key_file'))->toBeFalse()
        ->and(config('filesystems.disks.r2.secret'))->toBe('database-r2-secret')
        ->and(config('filesystems.disks.r2.use_path_style_endpoint'))->toBeTrue();
});

it('falls back to environment configuration when a setting is missing or removed', function () {
    config()->set('api_rate_limits.catalog_per_minute', 300);
    $category = runtimeSettingCategory('API_RATE_LIMITING', 'api-rate-limiting');
    $setting = runtimeSetting($category, 'API_RATE_LIMIT_CATALOG_PER_MINUTE', 'integer', 25);
    $settings = new RuntimeSettingService;

    $settings->applyToConfiguration();
    expect(config('api_rate_limits.catalog_per_minute'))->toBe(25);

    $setting->delete();
    $settings->forget();
    $settings->applyToConfiguration();

    expect(config('api_rate_limits.catalog_per_minute'))->toBe(300);
});

it('uses private settings internally without exposing them through the public settings endpoint', function () {
    $payments = runtimeSettingCategory('PAYMENTS', 'payments');
    runtimeSetting($payments, 'PAYSTACK_SECRET_KEY', 'string', 'private-secret', false);
    runtimeSetting($payments, 'PAYSTACK_PUBLIC_KEY', 'string', 'public-key', true);

    $settings = new RuntimeSettingService;

    expect($settings->value('PAYSTACK_SECRET_KEY'))->toBe('private-secret');

    $this->getJson('/api/v1/settings?category=payments')
        ->assertOk()
        ->assertJsonPath('data.PAYSTACK_PUBLIC_KEY', 'public-key')
        ->assertJsonMissing(['PAYSTACK_SECRET_KEY' => 'private-secret']);
});

it('resolves valid unique admin notification recipients from comma-separated settings', function () {
    $category = runtimeSettingCategory('ORDER NOTIFICATION MAILS', 'order-notification-mails');
    runtimeSetting($category, 'NEW_ORDER_EMAILS', 'string', ' Orders@Example.com, finance@example.com, invalid, orders@example.com ');
    runtimeSetting($category, 'PAYMENT_FAILED_EMAILS', 'string', 'payments@example.com, support@example.com');

    $resolver = new NotificationRecipientResolver(new RuntimeSettingService);

    expect($resolver->resolve(
        'NEW_ORDER_EMAILS',
        'services.notifications.new_order_emails',
        ['owner@example.com'],
    ))->toBe(['owner@example.com', 'orders@example.com', 'finance@example.com'])
        ->and($resolver->resolve(
            'PAYMENT_FAILED_EMAILS',
            'services.notifications.payment_failed_emails',
        ))->toBe(['payments@example.com', 'support@example.com']);
});

function runtimeSettingCategory(string $name, string $slug): SettingCategory
{
    return SettingCategory::query()->create([
        'name' => $name,
        'slug' => $slug,
        'is_active' => true,
    ]);
}

function runtimeSetting(
    SettingCategory $category,
    string $key,
    string $type,
    mixed $value,
    bool $public = false,
): Setting {
    return Setting::query()->create([
        'setting_category_id' => $category->id,
        'key' => $key,
        'value' => ['value' => $value],
        'value_type' => $type,
        'is_public' => $public,
        'is_active' => true,
    ]);
}
