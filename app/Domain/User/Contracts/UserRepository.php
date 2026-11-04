<?php

namespace App\Domain\User\Contracts;

use App\Domain\User\Entities\UserEntity;

interface UserRepository
{
    public function findByEmail(string $email): ?UserEntity;

    public function findById(string $id): ?UserEntity;

    public function create(array $data): UserEntity;
}
