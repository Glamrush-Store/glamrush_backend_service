<?php

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
    ]);

    $rateLimits = runtimeSettingCategory('API_RATE_LIMITING', 'api-rate-limiting');
    $payments = runtimeSettingCategory('PAYMENTS', 'payments');
    $media = runtimeSettingCategory('MEDIA STORAGE', 'media-storage');

    runtimeSetting($rateLimits, 'API_RATE_LIMIT_GENERAL_PER_MINUTE', 'integer', 45);
    runtimeSetting($payments, 'PAYSTACK_SECRET_KEY', 'string', 'database-secret');
    runtimeSetting($media, 'USE_GCP_KEY_FILE', 'boolean', false);

    $settings = new RuntimeSettingService;
    $settings->applyToConfiguration();

    expect(config('api_rate_limits.general_per_minute'))->toBe(45)
        ->and(config('services.paystack.secret_key'))->toBe('database-secret')
        ->and(config('filesystems.disks.gcs.use_key_file'))->toBeFalse();
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
