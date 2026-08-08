<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use App\Domain\Content\Concerns\HasPublicPublicationScope;
use App\Domain\Content\Enums\ContentPageType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

final class ContentPage extends Model implements HasMedia
{
    use HasPublicPublicationScope, HasUlids, InteractsWithMedia, SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'page_type' => ContentPageType::class,
            'settings' => 'array',
            'is_published' => 'boolean',
            'published_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
            'applies_to_all_storefronts' => 'boolean',
            'display_order' => 'integer',
        ];
    }

    public function storefronts(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'content_page_storefronts', 'content_page_id', 'category_id');
    }

    public function scopeForStorefront(Builder $query, string $rootCategoryId): Builder
    {
        return $query->where(fn (Builder $targeting) => $targeting
            ->where('applies_to_all_storefronts', true)
            ->orWhereHas('storefronts', fn (Builder $storefront) => $storefront->whereKey($rootCategoryId)));
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('content-images')->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }
}
