<?php

/*
 * (c) 2025 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Abbasudo\Purity\Traits\Filterable;
use Abbasudo\Purity\Traits\Sortable;
use App\Infrastructure\Persistence\Eloquent\Scopes\PublishedScope;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Product extends Model implements HasMedia
{
    use Filterable, HasUlids, InteractsWithMedia, Sortable;

    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'slug',
        'short_description',
        'description',
        'type',
        'status',
        'published_at',
        'meta_title',
        'meta_description',
        'is_featured',
        'sort_order',
        'brand_id',
        'manage_stock',
        'stock_quantity',
        'in_stock',
    ];

    protected $keyType = 'string';

    protected $table = 'products';

    protected $casts = [
        'published_at' => 'datetime',
    ];

    protected array $filterable = [
        'search',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new PublishedScope);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('catalog-photos')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'])
            ->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(300)
            ->height(300)
            ->sharpen(10);

        $this->addMediaConversion('medium')
            ->fit(Fit::Max, 800, 800)
            ->nonQueued();
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_product')
            ->using(CategoryProduct::class)
            ->withPivot(['id', 'is_primary', 'sequence'])
            ->withTimestamps()
            ->orderByPivot('sequence');
    }

    public function primaryCategory(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_product')
            ->using(CategoryProduct::class)
            ->withPivot(['id', 'is_primary', 'sequence'])
            ->wherePivot('is_primary', true)
            ->withTimestamps();
    }

    public function category(): BelongsToMany
    {
        return $this->primaryCategory();
    }

    public function getCategoryIdAttribute(): ?string
    {
        if ($this->relationLoaded('primaryCategory') && $this->primaryCategory->isNotEmpty()) {
            return (string) $this->primaryCategory->first()->id;
        }

        if ($this->relationLoaded('categories') && $this->categories->isNotEmpty()) {
            $primary = $this->categories->first(fn ($category) => (bool) ($category->pivot?->is_primary));
            return (string) ($primary?->id ?? $this->categories->first()->id);
        }

        return $this->categories()->wherePivot('is_primary', true)->value('categories.id')
            ?: $this->categories()->value('categories.id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(ProductCollection::class, 'collection_product', 'product_id', 'collection_id')
            ->using(CollectionProduct::class);
    }

    public function defaultVariant()
    {
        return $this->hasOne(ProductVariant::class)
            ->where('is_default', true);
    }
}

