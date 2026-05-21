<?php

namespace App\Domain\User\Entities;

final class AddressEntity
{
    public function __construct(
        public readonly string $id,
        public readonly string $userId,
        public readonly ?string $label,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly ?string $phone,
        public readonly string $addressLine1,
        public readonly ?string $addressLine2,
        public readonly string $country,
        public readonly string $state,
        public readonly string $city,
        public readonly string $postalCode,
        public readonly bool $isDefault,
    ) {}
}
