<?php

namespace App\Domain\Catalog\Cart;

final class CartIdentifier
{
    public function __construct(
        public readonly ?int $userId,
        public readonly ?string $cartToken,
    ) {}

    public function isGuest(): bool
    {
        return $this->userId === null;
    }
}
