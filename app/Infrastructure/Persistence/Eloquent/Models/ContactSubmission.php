<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class ContactSubmission extends Model
{
    use HasUlids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'storefront_category_id', 'customer_account_id', 'name', 'email', 'phone', 'subject',
        'message', 'status', 'source', 'metadata', 'duplicate_fingerprint', 'deduplication_bucket', 'resolved_at',
    ];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'resolved_at' => 'immutable_datetime'];
    }
}
