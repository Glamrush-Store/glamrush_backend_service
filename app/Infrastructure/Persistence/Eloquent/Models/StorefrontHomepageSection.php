<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class StorefrontHomepageSection extends Model
{
    use HasUlids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'storefront_homepage_sections';

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'config' => 'array',
            'display_order' => 'integer',
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'storefront_homepage_section_product', 'section_id', 'product_id')
            ->using(StorefrontHomepageSectionProduct::class)
            ->withPivot('display_order')
            ->orderByPivot('display_order');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeCurrentlyScheduled(Builder $query, mixed $at = null): Builder
    {
        $at ??= now();

        return $query
            ->where(fn (Builder $builder) => $builder->whereNull('starts_at')->orWhere('starts_at', '<=', $at))
            ->where(fn (Builder $builder) => $builder->whereNull('ends_at')->orWhere('ends_at', '>', $at));
    }

    public function scopePublished(Builder $query, mixed $at = null): Builder
    {
        return $query->active()->currentlyScheduled($at);
    }

    public function scopeForStorefront(Builder $query, string $slug): Builder
    {
        return $query->where('storefront_slug', $slug);
    }

    public function scopeInDisplayOrder(Builder $query): Builder
    {
        return $query->orderBy('display_order')->orderBy('id');
    }
}
