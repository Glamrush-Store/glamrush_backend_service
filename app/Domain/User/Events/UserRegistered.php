<?php

namespace App\Domain\User\Events;

final class UserRegistered
{
    public function __construct(
        public readonly string $userId,
    ) {
    }
}
