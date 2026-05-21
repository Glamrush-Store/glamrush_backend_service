<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use App\Infrastructure\Persistence\Eloquent\Scopes\NotExpiredScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    protected $fillable = ['user_id', 'cart_token', 'product_id', 'quantity', 'expires_at'];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new NotExpiredScope);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
