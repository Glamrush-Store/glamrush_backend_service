<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Catalog\Cart\CartIdentifier;
use App\Domain\Catalog\Cart\Contracts\CartRepository;
use App\Domain\Catalog\Cart\Entities\CartItemEntity;
use App\Domain\Catalog\Cart\Exceptions\InsufficientStockException;
use App\Infrastructure\Persistence\Eloquent\Mappers\Catalog\CartItemMapper;
use App\Infrastructure\Persistence\Eloquent\Models\CartItem;
use App\Infrastructure\Persistence\Eloquent\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class EloquentCartRepository implements CartRepository
{
    public function getItems(CartIdentifier $id): Collection
    {
        return CartItem::with(['product', 'product.media'])
            ->tap(fn(Builder $q) => $this->constrain($q, $id))
            ->get()
            ->map(fn(CartItem $item) => CartItemMapper::toDomain($item));
    }

    private function constrain(Builder $query, CartIdentifier $id): void
    {
        if ($id->isGuest()) {
            $query->where('cart_token', $id->cartToken);
        } else {
            $query->where('user_id', $id->userId);
        }
    }

    public function addItem(CartIdentifier $id, string $productId, int $quantity): CartItemEntity
    {
        $existing = CartItem::withoutGlobalScopes()
            ->tap(fn(Builder $q) => $this->constrain($q, $id))
            ->where('product_id', $productId)
            ->first();

        $this->checkStock($productId, $existing ? $existing->quantity + $quantity : $quantity);

        if ($existing) {
            $existing->quantity += $quantity;
            $existing->expires_at = now()->addHour();
            $existing->save();
            $item = $existing;
        } else {
            $attrs = array_merge($this->identifierAttrs($id), [
                'product_id' => $productId,
                'quantity' => $quantity,
                'expires_at' => now()->addHour(),
            ]);
            $item = CartItem::create($attrs);
        }

        $item->load(['product', 'product.media']);

        return CartItemMapper::toDomain($item);
    }

    private function checkStock(string $productId, int $requestedQty): void
    {
        $product = Product::withoutGlobalScopes()->find($productId);

        if (!$product || $product->type === 'variable' || !$product->manage_stock) {
            return;
        }

        if ($product->stock_quantity < $requestedQty) {
            throw new InsufficientStockException((int)$product->stock_quantity);
        }
    }

    private function identifierAttrs(CartIdentifier $id): array
    {
        if ($id->isGuest()) {
            return ['cart_token' => $id->cartToken];
        }

        return ['user_id' => $id->userId];
    }

    public function updateItem(CartIdentifier $id, string $productId, int $quantity): CartItemEntity
    {
        $this->checkStock($productId, $quantity);

        $item = CartItem::withoutGlobalScopes()
            ->tap(fn(Builder $q) => $this->constrain($q, $id))
            ->where('product_id', $productId)
            ->firstOrFail();

        $item->quantity = $quantity;
        $item->expires_at = now()->addHour();
        $item->save();

        $item->load(['product', 'product.media']);

        return CartItemMapper::toDomain($item);
    }

    public function removeItem(CartIdentifier $id, string $productId): void
    {
        CartItem::withoutGlobalScopes()
            ->tap(fn(Builder $q) => $this->constrain($q, $id))
            ->where('product_id', $productId)
            ->firstOrFail()
            ->delete();
    }

    public function clearCart(CartIdentifier $id): void
    {
        CartItem::withoutGlobalScopes()
            ->tap(fn(Builder $q) => $this->constrain($q, $id))
            ->delete();
    }

    public function mergeGuestCart(int $userId, string $cartToken): void
    {
        $guestItems = CartItem::with(['product', 'product.media'])
            ->where('cart_token', $cartToken)
            ->get();

        foreach ($guestItems as $guestItem) {
            $existing = CartItem::withoutGlobalScopes()
                ->where('user_id', $userId)
                ->where('product_id', $guestItem->product_id)
                ->first();

            if ($existing) {
                $existing->quantity += $guestItem->quantity;
                $existing->expires_at = now()->addHour();
                $existing->save();
            } else {
                CartItem::create([
                    'user_id' => $userId,
                    'product_id' => $guestItem->product_id,
                    'quantity' => $guestItem->quantity,
                    'expires_at' => now()->addHour(),
                ]);
            }
        }

        CartItem::withoutGlobalScopes()
            ->where('cart_token', $cartToken)
            ->delete();
    }
}
