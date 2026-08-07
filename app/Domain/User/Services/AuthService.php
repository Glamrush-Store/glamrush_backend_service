<?php

namespace App\Domain\User\Services;

use App\Domain\User\Contracts\SocialAccountRepository;
use App\Domain\User\Contracts\UserRepository;
use App\Domain\User\Entities\UserEntity;
use App\Domain\User\Events\UserRegistered;
use App\Infrastructure\Outbox\OutboxService;
use App\Infrastructure\Persistence\Eloquent\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

final class AuthService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly SocialAccountRepository $socialAccountRepository,
        private readonly OutboxService $outbox,
    ) {}

    /**
     * @return array{user: UserEntity, token: string}
     */
    public function register(array $data): array
    {
        $userEntity = $this->userRepository->create($data);
        $user = User::findOrFail($userEntity->id);
        $token = $user->createToken('api')->plainTextToken;

        $this->recordRegistrationEvent((string) $user->id);

        return ['user' => $userEntity, 'token' => $token];
    }

    /**
     * @return array{user: UserEntity, token: string}|null
     */
    public function login(string $email, string $password): ?array
    {
        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            return null;
        }

        $userEntity = $this->userRepository->findByEmail($email);
        $token = $user->createToken('api')->plainTextToken;

        return ['user' => $userEntity, 'token' => $token];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }

    /**
     * @return array{user: UserEntity, token: string}
     */
    public function handleSocialAuth(string $provider, string $accessToken): array
    {
        $socialUser = Socialite::driver($provider)->userFromToken($accessToken);

        $existingUser = $this->socialAccountRepository->findByProvider($provider, $socialUser->getId());

        if ($existingUser) {
            $userEntity = $this->userRepository->findById((string) $existingUser->id);
            $token = $existingUser->createToken('api')->plainTextToken;

            return ['user' => $userEntity, 'token' => $token];
        }

        $userByEmail = User::where('email', $socialUser->getEmail())->first();

        if (! $userByEmail) {
            $userEntity = $this->userRepository->create([
                'name' => $socialUser->getName() ?? $socialUser->getNickname() ?? 'User',
                'email' => $socialUser->getEmail(),
                'password' => Str::random(32),
            ]);
            $userByEmail = User::findOrFail($userEntity->id);
            $this->recordRegistrationEvent((string) $userByEmail->id);
        } else {
            $userEntity = $this->userRepository->findById((string) $userByEmail->id);
        }

        $this->socialAccountRepository->createForUser($userByEmail, $provider, $socialUser);
        $token = $userByEmail->createToken('api')->plainTextToken;

        return ['user' => $userEntity, 'token' => $token];
    }

    private function recordRegistrationEvent(string $userId): void
    {
        $this->outbox->recordEvent(
            "user:{$userId}:registered",
            new UserRegistered($userId),
        );
    }
}
