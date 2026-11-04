<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    use HasUlids;

    protected $keyType = 'string';


    protected $casts = [
        'is_active' => 'boolean'
    ];

}
