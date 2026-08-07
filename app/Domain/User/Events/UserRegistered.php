<?php

namespace App\Domain\User\Events;

use App\Shared\Events\Contracts\DomainEvent;

final class UserRegistered implements DomainEvent
{
    public function __construct(
        public readonly string $userId,
    ) {}

    public function eventType(): string
    {
        return 'user.registered';
    }

    public function eventPayload(): array
    {
        return ['user_id' => $this->userId];
    }
}
