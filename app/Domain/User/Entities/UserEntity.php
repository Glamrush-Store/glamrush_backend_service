<?php

namespace App\Domain\User\Entities;

final class UserEntity
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $phone,
        public readonly \DateTimeImmutable $createdAt,
    ) {}
}
