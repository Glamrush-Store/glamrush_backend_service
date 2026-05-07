<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\User\Contracts\SocialAccountRepository;
use App\Infrastructure\Persistence\Eloquent\Models\SocialAccount;
use App\Infrastructure\Persistence\Eloquent\Models\User;
use Laravel\Socialite\Contracts\User as SocialUser;

final class EloquentSocialAccountRepository implements SocialAccountRepository
{
    public function findByProvider(string $provider, string $providerId): ?User
    {
        $account = SocialAccount::with('user')
            ->where('provider', $provider)
            ->where('provider_user_id', $providerId)
            ->first();

        return $account?->user;
    }

    public function createForUser(User $user, string $provider, SocialUser $socialUser): void
    {
        SocialAccount::create([
            'user_id' => $user->id,
            'provider' => $provider,
            'provider_user_id' => $socialUser->getId(),
            'access_token' => $socialUser->token ?? '',
            'refresh_token' => $socialUser->refreshToken ?? null,
            'expires_at' => $socialUser->expiresIn
                ? now()->addSeconds($socialUser->expiresIn)
                : null,
        ]);
    }
}
