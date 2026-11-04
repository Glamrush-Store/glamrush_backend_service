<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class DiscountCodeTarget extends Model
{
    use HasUlids;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];
}
