<?php

namespace App\Shared\Idempotency;

use RuntimeException;

final class IdempotencyOwner
{
    public static function from(?int $userId, ?string $guestToken): string
    {
        if ($userId !== null) {
            return "user:{$userId}";
        }

        $guestToken = trim((string) $guestToken);

        if ($guestToken === '') {
            throw new RuntimeException('A cart token is required for guest requests.');
        }

        return "guest:{$guestToken}";
    }
}
