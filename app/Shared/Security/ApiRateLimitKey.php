<?php

namespace App\Shared\Security;

use Illuminate\Http\Request;

final class ApiRateLimitKey
{
    public static function email(mixed $email): string
    {
        $normalized = is_string($email) ? mb_strtolower(trim($email)) : '';

        return hash('sha256', $normalized);
    }

    public static function ip(Request $request): string
    {
        return hash('sha256', (string) $request->ip());
    }

    public static function customer(Request $request): string
    {
        if ($user = $request->user('sanctum')) {
            return 'user:'.$user->getAuthIdentifier();
        }

        $cartToken = $request->header('X-Cart-Token');

        if (is_string($cartToken) && $cartToken !== '') {
            return 'guest:'.hash('sha256', $cartToken);
        }

        return 'ip:'.self::ip($request);
    }

    public static function passwordResetGenerationCacheKey(mixed $email): string
    {
        return 'security:password-reset-generation:'.self::email($email);
    }
}
