<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    use HasUlids;

    public $incrementing = false;
    protected $fillable = [
        'id',
        'name',
        'slug',
        'description',
        'is_active',
        'meta_title',
        'meta_description',
        'sort_order'
    ];
    protected $keyType = 'string';

    protected $table = 'brands';


    protected $casts = [
        'is_active' => 'boolean'
    ];

}
