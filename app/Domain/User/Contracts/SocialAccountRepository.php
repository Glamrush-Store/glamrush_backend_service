<?php

namespace App\Domain\User\Contracts;

use App\Infrastructure\Persistence\Eloquent\Models\User;
use Laravel\Socialite\Contracts\User as SocialUser;

interface SocialAccountRepository
{
    public function findByProvider(string $provider, string $providerId): ?User;

    public function createForUser(User $user, string $provider, SocialUser $socialUser): void;
}
