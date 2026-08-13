<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\Pivot;

class CategoryProduct extends Pivot
{
    use HasUlids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'category_product';

    protected $fillable = [
        'id',
        'product_id',
        'category_id',
        'is_primary',
        'sequence',
    ];
}
