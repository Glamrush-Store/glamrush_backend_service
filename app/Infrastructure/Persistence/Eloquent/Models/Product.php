<?php
/*
 * © 2025 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Abbasudo\Purity\Traits\Filterable;
use Abbasudo\Purity\Traits\Sortable;
use App\Infrastructure\Persistence\Eloquent\Scopes\PublishedScope;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Product extends Model implements HasMedia
{
    use HasUlids, Filterable, Sortable, interactsWithMedia;

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
        'category_id',
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
        'search',   // custom filter
        // add others later if needed
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

    public function registerMediaConversions(Media $media = null): void
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

    public function category()
    {
        return $this->belongsTo(Category::class);
    }


    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function defaultVariant()
    {
        return $this->hasOne(ProductVariant::class)
            ->where('is_default', true);
    }

}
