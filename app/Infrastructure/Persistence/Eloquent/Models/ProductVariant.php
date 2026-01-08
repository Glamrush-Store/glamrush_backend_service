<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    use HasUlids;

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

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    
}
