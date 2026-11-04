<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use App\Domain\Order\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasUlids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'guest_id',
        'idempotency_owner',
        'idempotency_key',
        'idempotency_request_hash',
        'order_number',
        'status',
        'discount_code_id',
        'discount_code',
        'subtotal',
        'discount_amount',
        'shipping_amount',
        'shipping_discount_amount',
        'discount_snapshot',
        'total',
        'currency',
        'shipping_rate_id',
        'shipping_method_name',
        'shipping_zone_name',
        'shipping_address',
        'billing_address',
        'placed_at',
        'paid_at',
        'inventory_committed_at',
        'inventory_released_at',
        'expires_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'shipping_amount' => 'decimal:2',
            'shipping_discount_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'discount_snapshot' => 'array',
            'shipping_address' => 'array',
            'billing_address' => 'array',
            'status' => OrderStatus::class,
            'placed_at' => 'datetime',
            'paid_at' => 'datetime',
            'inventory_committed_at' => 'datetime',
            'inventory_released_at' => 'datetime',
            'expires_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'order_id');
    }

    public function shippingRate(): BelongsTo
    {
        return $this->belongsTo(ShippingRate::class, 'shipping_rate_id');
    }
}
