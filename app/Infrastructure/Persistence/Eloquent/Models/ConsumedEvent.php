<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;

final class ConsumedEvent extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'type',
        'processed_at',
    ];

    protected function casts(): array
    {
        return ['processed_at' => 'immutable_datetime'];
    }
}
