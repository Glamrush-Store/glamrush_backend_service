<?php

namespace App\Shared\Idempotency;

final class IdempotencyFingerprint
{
    public static function from(array $payload): string
    {
        return hash('sha256', json_encode(self::canonicalize($payload), JSON_THROW_ON_ERROR));
    }

    private static function canonicalize(array $value): array
    {
        if (! array_is_list($value)) {
            ksort($value);
        }

        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = self::canonicalize($item);
            }
        }

        return $value;
    }
}
