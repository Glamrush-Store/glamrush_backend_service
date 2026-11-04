<?php

namespace App\Domain\User\Services;

use App\Domain\User\Notifications\PasswordResetCodeNotification;
use App\Infrastructure\Persistence\Eloquent\Models\User;
use App\Shared\Security\ApiRateLimitKey;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class PasswordService
{
    public function sendResetCode(string $email): void
    {
        $user = User::where('email', $email)->first();

        if (! $user) {
            // No email enumeration — silently return
            return;
        }

        $code = (string) random_int(100000, 999999);

        DB::table('password_reset_codes')->upsert(
            [
                'email' => $email,
                'code' => Hash::make($code),
                'verified' => false,
                'expires_at' => Carbon::now()->addMinutes(15),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            ['email'],
            ['code', 'verified', 'expires_at', 'updated_at'],
        );

        Cache::put(
            ApiRateLimitKey::passwordResetGenerationCacheKey($email),
            (string) Str::uuid(),
            Carbon::now()->addMinutes(15),
        );

        $user->notify(new PasswordResetCodeNotification($code));
    }

    public function verifyCode(string $email, string $code): void
    {
        $record = DB::table('password_reset_codes')->where('email', $email)->first();

        if (
            ! $record
            || ! Hash::check($code, $record->code)
            || Carbon::parse($record->expires_at)->isPast()
        ) {
            throw ValidationException::withMessages([
                'code' => ['The provided code is invalid or has expired.'],
            ]);
        }

        DB::table('password_reset_codes')
            ->where('email', $email)
            ->update(['verified' => true, 'updated_at' => Carbon::now()]);
    }

    public function resetPassword(string $email, string $password): void
    {
        $record = DB::table('password_reset_codes')
            ->where('email', $email)
            ->where('verified', true)
            ->first();

        if (! $record) {
            throw ValidationException::withMessages([
                'email' => ['Password reset has not been verified for this email.'],
            ]);
        }

        User::where('email', $email)->update([
            'password' => Hash::make($password),
        ]);

        DB::table('password_reset_codes')->where('email', $email)->delete();
        Cache::forget(ApiRateLimitKey::passwordResetGenerationCacheKey($email));
    }
}
