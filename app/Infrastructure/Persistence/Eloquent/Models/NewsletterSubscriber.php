<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use App\Domain\Newsletter\Enums\NewsletterSubscriptionStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class NewsletterSubscriber extends Model
{
    use HasUlids;

    protected $fillable = [
        'email',
        'status',
        'source',
        'confirmation_token_hash',
        'unsubscribe_token_hash',
        'confirmation_expires_at',
        'consented_at',
        'confirmed_at',
        'unsubscribed_at',
        'consent_ip_hash',
        'consent_user_agent',
    ];

    protected function casts(): array
    {
        return [
            'status' => NewsletterSubscriptionStatus::class,
            'confirmation_expires_at' => 'immutable_datetime',
            'consented_at' => 'immutable_datetime',
            'confirmed_at' => 'immutable_datetime',
            'unsubscribed_at' => 'immutable_datetime',
        ];
    }
}
