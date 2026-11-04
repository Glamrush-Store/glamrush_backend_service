<?php

namespace App\Domain\Catalog\Cart\Contracts;

use App\Domain\Catalog\Cart\CartIdentifier;
use App\Domain\Catalog\Cart\Entities\CartItemEntity;
use Illuminate\Support\Collection;

interface CartRepository
{
    public function getItems(CartIdentifier $id): Collection;

    public function addItem(
        CartIdentifier $id,
        string $productId,
        ?string $productVariantId,
        int $quantity,
    ): CartItemEntity;

    public function updateItem(CartIdentifier $id, string $productId, int $quantity): CartItemEntity;

    public function removeItem(CartIdentifier $id, string $productId): void;

    public function updateItemById(CartIdentifier $id, int $itemId, int $quantity): CartItemEntity;

    public function removeItemById(CartIdentifier $id, int $itemId): void;

    public function clearCart(CartIdentifier $id): void;

    public function mergeGuestCart(int $userId, string $cartToken): void;

    public function hasGuestItems(string $cartToken): bool;
}
