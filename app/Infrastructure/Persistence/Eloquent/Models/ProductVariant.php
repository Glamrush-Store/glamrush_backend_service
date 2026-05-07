<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProductVariant extends Model implements HasMedia
{
    use HasUlids, InteractsWithMedia;

    public $incrementing = false;
    protected $fillable = [
        'id',
        'product_id',
        'sku',
        'is_default',
        'price',
        'sale_price',
        'sale_starts_at',
        'sale_ends_at',
        'manage_stock',
        'stock_quantity',
        'in_stock',
        'attributes',
        'sort_order',
        'status',
    ];
    protected $keyType = 'string';

    protected $casts = [
        'attributes' => 'array',
        'sale_starts_at' => 'datetime',
        'sale_ends_at' => 'datetime',
    ];
    protected $table = 'product_variants';


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

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

}
