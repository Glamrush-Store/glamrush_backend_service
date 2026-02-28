<?php

namespace App\Infrastructure\Persistence\Eloquent\Mappers;

use App\Domain\User\Entities\UserEntity;
use App\Models\User;

final class UserMapper
{
    public static function toDomain(User $model): UserEntity
    {
        return new UserEntity(
            id: (string) $model->id,
            name: $model->name,
            email: $model->email,
            phone: $model->phone,
            createdAt: \DateTimeImmutable::createFromInterface($model->created_at),
        );
    }
}
