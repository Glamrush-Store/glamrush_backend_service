<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use App\Infrastructure\Persistence\Eloquent\Scopes\NotExpiredScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    protected $fillable = [
        'user_id',
        'cart_token',
        'product_id',
        'product_variant_id',
        'quantity',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new NotExpiredScope);

        static::creating(function (CartItem $item): void {
            if ($item->product_variant_id !== null) {
                return;
            }

            $product = Product::withoutGlobalScopes()->find($item->product_id);

            if (! $product || $product->type !== 'simple') {
                return;
            }

            $item->product_variant_id = ProductVariant::query()
                ->where('product_id', $item->product_id)
                ->where('is_default', true)
                ->whereIn('status', ProductVariant::SELLABLE_STATUSES)
                ->value('id');
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
