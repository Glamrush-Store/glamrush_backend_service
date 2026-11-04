<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class DiscountCode extends Model
{
    use HasUlids, SoftDeletes;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'maximum_discount_amount' => 'decimal:2',
            'minimum_subtotal' => 'decimal:2',
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
            'is_active' => 'boolean',
            'first_order_only' => 'boolean',
            'applies_to_sale_items' => 'boolean',
            'applies_to_all_storefronts' => 'boolean',
        ];
    }

    public function storefronts(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'discount_code_storefronts', 'discount_code_id', 'category_id');
    }

    public function targets(): HasMany
    {
        return $this->hasMany(DiscountCodeTarget::class);
    }
}
