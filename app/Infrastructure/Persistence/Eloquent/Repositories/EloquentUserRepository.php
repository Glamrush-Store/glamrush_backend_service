<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\User\Contracts\UserRepository;
use App\Domain\User\Entities\UserEntity;
use App\Infrastructure\Persistence\Eloquent\Mappers\UserMapper;
use App\Models\User;

final class EloquentUserRepository implements UserRepository
{
    public function findByEmail(string $email): ?UserEntity
    {
        $user = User::where('email', $email)->first();

        return $user ? UserMapper::toDomain($user) : null;
    }

    public function findById(string $id): ?UserEntity
    {
        $user = User::find($id);

        return $user ? UserMapper::toDomain($user) : null;
    }

    public function create(array $data): UserEntity
    {
        $user = User::create($data);

        return UserMapper::toDomain($user);
    }
}
