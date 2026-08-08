<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use App\Domain\Content\Concerns\HasPublicPublicationScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Faq extends Model
{
    use HasPublicPublicationScope, HasUlids, SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'display_order' => 'integer', 'is_published' => 'boolean',
            'published_at' => 'immutable_datetime', 'expires_at' => 'immutable_datetime',
            'applies_to_all_storefronts' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(FaqCategory::class, 'faq_category_id');
    }

    public function storefronts(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'faq_storefronts', 'faq_id', 'category_id');
    }

    public function scopeForStorefront(Builder $query, string $rootCategoryId): Builder
    {
        return $query->where(fn (Builder $targeting) => $targeting
            ->where('faqs.applies_to_all_storefronts', true)
            ->orWhereHas('storefronts', fn (Builder $storefront) => $storefront->whereKey($rootCategoryId)));
    }
}
