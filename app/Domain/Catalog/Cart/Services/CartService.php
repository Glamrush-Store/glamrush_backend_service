<?php

namespace App\Domain\Catalog\Cart\Services;

use App\Domain\Catalog\Cart\CartIdentifier;
use App\Domain\Catalog\Cart\Contracts\CartRepository;
use App\Domain\Catalog\Cart\Entities\CartItemEntity;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class CartService
{
    public function __construct(
        private readonly CartRepository $repository,
    ) {}

    public function getCart(CartIdentifier $id): array
    {
        return [
            'items'      => $this->repository->getItems($id),
            'cart_token' => $id->cartToken,
        ];
    }

    public function add(CartIdentifier $id, string $productId, int $quantity): array
    {
        if ($id->isGuest() && $id->cartToken === null) {
            $id = new CartIdentifier(null, Str::uuid()->toString());
        }

        $entity = $this->repository->addItem($id, $productId, $quantity);

        return [
            'entity'     => $entity,
            'cart_token' => $id->cartToken,
            'created'    => true,
        ];
    }

    public function update(CartIdentifier $id, string $productId, int $quantity): CartItemEntity
    {
        return $this->repository->updateItem($id, $productId, $quantity);
    }

    public function remove(CartIdentifier $id, string $productId): void
    {
        $this->repository->removeItem($id, $productId);
    }

    public function clear(CartIdentifier $id): void
    {
        $this->repository->clearCart($id);
    }

    public function merge(int $userId, string $cartToken): Collection
    {
        $this->repository->mergeGuestCart($userId, $cartToken);

        return $this->repository->getItems(new CartIdentifier($userId, null));
    }
}
