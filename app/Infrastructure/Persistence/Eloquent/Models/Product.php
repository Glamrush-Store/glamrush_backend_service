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

class Product extends Model
{
    use HasUlids, Filterable, Sortable;

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


    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
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
