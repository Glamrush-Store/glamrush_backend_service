<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class DiscountRedemption extends Model
{
    use HasUlids;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'discount_amount' => 'decimal:2',
            'shipping_discount_amount' => 'decimal:2',
            'snapshot' => 'array',
            'reserved_at' => 'immutable_datetime',
            'redeemed_at' => 'immutable_datetime',
            'released_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
        ];
    }
}
