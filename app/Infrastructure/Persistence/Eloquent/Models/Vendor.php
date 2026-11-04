<?php
/*
 * © 2026 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */


namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use HasUlids;

    protected $keyType = 'string';
    

    protected $casts = [
        'is_active' => 'boolean'
    ];

}
