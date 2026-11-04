<?php

namespace App\Domain\Setting\Services;

use App\Infrastructure\Persistence\Eloquent\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SiteSettingService
{
    public function publicSettings(): array
    {
        return Cache::remember('site-settings:public:all', now()->addMinutes(5), function (): array {
            return Setting::query()
                ->with('category')
                ->where('is_active', true)
                ->where('is_public', true)
                ->whereHas('category', fn ($query) => $query->where('is_active', true))
                ->get()
                ->groupBy(fn (Setting $setting) => $setting->category->slug)
                ->map(fn ($settings) => $settings->mapWithKeys(fn (Setting $setting) => [$setting->key => $setting->decodedValue()])->all())
                ->all();
        });
    }

    public function get(?string $category = null, ?string $key = null): mixed
    {
        $settings = $this->publicSettings();

        if ($category === null) {
            return $settings;
        }

        if ($key === null) {
            return $settings[$category] ?? [];
        }

        return $settings[$category][$key] ?? null;
    }
}
