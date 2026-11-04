<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

final class CollectionProduct extends Pivot
{
    public $timestamps = false;

    protected $table = 'collection_product';
}
