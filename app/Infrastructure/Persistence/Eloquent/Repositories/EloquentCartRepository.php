<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Catalog\Cart\CartIdentifier;
use App\Domain\Catalog\Cart\Contracts\CartRepository;
use App\Domain\Catalog\Cart\Entities\CartItemEntity;
use App\Domain\Catalog\Cart\Exceptions\AmbiguousCartItemException;
use App\Domain\Catalog\Cart\Exceptions\InsufficientStockException;
use App\Domain\Catalog\Cart\Exceptions\InvalidCartSelectionException;
use App\Domain\Catalog\Storefront\StorefrontContext;
use App\Infrastructure\Persistence\Eloquent\Mappers\Catalog\CartItemMapper;
use App\Infrastructure\Persistence\Eloquent\Models\CartItem;
use App\Infrastructure\Persistence\Eloquent\Models\Product;
use App\Infrastructure\Persistence\Eloquent\Models\ProductVariant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class EloquentCartRepository implements CartRepository
{
    private const ITEM_RELATIONS = ['product', 'product.media', 'product.categories', 'product.primaryCategory', 'variant', 'variant.media'];

    private const CART_TTL_DAYS = 7;

    public function __construct(
        private readonly StorefrontContext $storefrontContext,
    ) {}

    public function getItems(CartIdentifier $id): Collection
    {
        return CartItem::with(self::ITEM_RELATIONS)
            ->tap(fn (Builder $query) => $this->constrain($query, $id))
            ->tap(fn (Builder $query) => $this->constrainStorefront($query))
            ->get()
            ->map(fn (CartItem $item) => CartItemMapper::toDomain($item));
    }

    public function addItem(
        CartIdentifier $id,
        string $productId,
        ?string $productVariantId,
        int $quantity,
    ): CartItemEntity {
        [$product, $variant] = $this->resolveSelection($productId, $productVariantId);

        $existing = CartItem::withoutGlobalScopes()
            ->tap(fn (Builder $query) => $this->constrain($query, $id))
            ->tap(fn (Builder $query) => $this->constrainStorefront($query))
            ->where('product_variant_id', $variant->id)
            ->first();

        $requestedQuantity = $existing ? $existing->quantity + $quantity : $quantity;
        $this->checkVariantStock($product, $variant, $requestedQuantity);

        if ($existing) {
            $existing->quantity = $requestedQuantity;
            $existing->expires_at = $this->freshExpiry();
            $existing->save();
            $item = $existing;
        } else {
            $item = CartItem::create(array_merge($this->identifierAttrs($id), [
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'quantity' => $quantity,
                'expires_at' => $this->freshExpiry(),
            ]));
        }

        return $this->mapItem($item);
    }

    public function updateItem(CartIdentifier $id, string $productId, int $quantity): CartItemEntity
    {
        $items = CartItem::withoutGlobalScopes()
            ->tap(fn (Builder $query) => $this->constrain($query, $id))
            ->tap(fn (Builder $query) => $this->constrainStorefront($query))
            ->where('product_id', $productId)
            ->limit(2)
            ->get();

        if ($items->count() > 1) {
            throw new AmbiguousCartItemException(
                'This product has multiple variants in the cart. Update the cart item by its item ID.'
            );
        }

        return $this->updateLoadedItem($this->firstItemOrFail($items), $quantity);
    }

    public function removeItem(CartIdentifier $id, string $productId): void
    {
        $items = CartItem::withoutGlobalScopes()
            ->tap(fn (Builder $query) => $this->constrain($query, $id))
            ->tap(fn (Builder $query) => $this->constrainStorefront($query))
            ->where('product_id', $productId)
            ->limit(2)
            ->get();

        if ($items->count() > 1) {
            throw new AmbiguousCartItemException(
                'This product has multiple variants in the cart. Remove the cart item by its item ID.'
            );
        }

        $this->firstItemOrFail($items)->delete();
    }

    public function updateItemById(CartIdentifier $id, int $itemId, int $quantity): CartItemEntity
    {
        return $this->updateLoadedItem($this->findOwnedItem($id, $itemId), $quantity);
    }

    public function removeItemById(CartIdentifier $id, int $itemId): void
    {
        $this->findOwnedItem($id, $itemId)->delete();
    }

    public function clearCart(CartIdentifier $id): void
    {
        CartItem::withoutGlobalScopes()
            ->tap(fn (Builder $query) => $this->constrain($query, $id))
            ->tap(fn (Builder $query) => $this->constrainStorefront($query))
            ->delete();
    }

    public function mergeGuestCart(int $userId, string $cartToken): void
    {
        DB::transaction(function () use ($userId, $cartToken): void {
            $guestItems = CartItem::with(self::ITEM_RELATIONS)
                ->where('cart_token', $cartToken)
                ->tap(fn (Builder $query) => $this->constrainStorefront($query))
                ->lockForUpdate()
                ->get();

            foreach ($guestItems as $guestItem) {
                $variant = $guestItem->variant ?? $guestItem->product?->defaultVariant()
                    ->whereIn('status', ProductVariant::SELLABLE_STATUSES)
                    ->first();

                if (! $guestItem->product || ! $variant) {
                    throw new InvalidCartSelectionException('One or more cart items are no longer available.');
                }

                $existing = CartItem::withoutGlobalScopes()
                    ->where('user_id', $userId)
                    ->where('product_variant_id', $variant->id)
                    ->lockForUpdate()
                    ->first();

                $mergedQuantity = ($existing?->quantity ?? 0) + $guestItem->quantity;
                $this->checkVariantStock($guestItem->product, $variant, $mergedQuantity);

                if ($existing) {
                    $existing->quantity = $mergedQuantity;
                    $existing->expires_at = $this->freshExpiry();
                    $existing->save();
                } else {
                    CartItem::create([
                        'user_id' => $userId,
                        'product_id' => $guestItem->product_id,
                        'product_variant_id' => $variant->id,
                        'quantity' => $guestItem->quantity,
                        'expires_at' => $this->freshExpiry(),
                    ]);
                }
            }

            CartItem::withoutGlobalScopes()
                ->whereKey($guestItems->modelKeys())
                ->delete();
        });
    }

    public function hasGuestItems(string $cartToken): bool
    {
        return CartItem::query()
            ->where('cart_token', $cartToken)
            ->exists();
    }

    /** @return array{Product, ProductVariant} */
    private function resolveSelection(string $productId, ?string $productVariantId): array
    {
        $product = Product::query()
            ->with(['categories', 'primaryCategory'])
            ->when($this->storefrontContext->isActive(), fn (Builder $query) => $query
                ->whereHas('categories', fn (Builder $categoryQuery) => $categoryQuery->whereIn('categories.id', $this->storefrontContext->categoryIds())))
            ->find($productId);

        if (! $product) {
            throw (new ModelNotFoundException)->setModel(Product::class, [$productId]);
        }

        if ($productVariantId === null && $product->type === 'variable') {
            throw new InvalidCartSelectionException('A product variant must be selected.');
        }

        $variantQuery = ProductVariant::query()
            ->where('product_id', $product->id)
            ->whereIn('status', ProductVariant::SELLABLE_STATUSES);

        $variant = $productVariantId
            ? $variantQuery->whereKey($productVariantId)->first()
            : $variantQuery->where('is_default', true)->first();

        if (! $variant) {
            throw new InvalidCartSelectionException('The selected product variant is not available.');
        }

        return [$product, $variant];
    }

    private function updateLoadedItem(CartItem $item, int $quantity): CartItemEntity
    {
        $item->loadMissing(['product', 'variant']);
        $variant = $item->variant ?? $item->product?->defaultVariant()
            ->whereIn('status', ProductVariant::SELLABLE_STATUSES)
            ->first();

        if (! $item->product || ! $variant) {
            throw new InvalidCartSelectionException('The selected product variant is not available.');
        }

        $this->checkVariantStock($item->product, $variant, $quantity);

        $item->product_variant_id = $variant->id;
        $item->quantity = $quantity;
        $item->expires_at = $this->freshExpiry();
        $item->save();

        return $this->mapItem($item);
    }

    private function findOwnedItem(CartIdentifier $id, int $itemId): CartItem
    {
        return CartItem::withoutGlobalScopes()
            ->tap(fn (Builder $query) => $this->constrain($query, $id))
            ->tap(fn (Builder $query) => $this->constrainStorefront($query))
            ->whereKey($itemId)
            ->firstOrFail();
    }

    private function firstItemOrFail(Collection $items): CartItem
    {
        $item = $items->first();

        if (! $item) {
            throw (new ModelNotFoundException)->setModel(CartItem::class);
        }

        return $item;
    }

    private function checkVariantStock(Product $product, ProductVariant $variant, int $requestedQuantity): void
    {
        if (! $variant->manage_stock) {
            return;
        }

        $available = $variant->in_stock
            ? max(0, (int) $variant->stock_quantity - (int) $variant->reserved_quantity)
            : 0;

        if ($available < $requestedQuantity) {
            throw new InsufficientStockException(
                productName: $product->name,
                available: $available,
            );
        }
    }

    private function mapItem(CartItem $item): CartItemEntity
    {
        $item->load(self::ITEM_RELATIONS);

        return CartItemMapper::toDomain($item);
    }

    private function constrain(Builder $query, CartIdentifier $id): void
    {
        if ($id->isGuest()) {
            $query->where('cart_token', $id->cartToken);

            return;
        }

        $query->where('user_id', $id->userId);
    }

    private function identifierAttrs(CartIdentifier $id): array
    {
        return $id->isGuest()
            ? ['cart_token' => $id->cartToken]
            : ['user_id' => $id->userId];
    }

    private function constrainStorefront(Builder $query): void
    {
        if (! $this->storefrontContext->isActive()) {
            return;
        }

        $query->whereHas('product', fn (Builder $productQuery) => $productQuery
            ->withoutGlobalScopes()
            ->whereHas('categories', fn (Builder $categoryQuery) => $categoryQuery->whereIn('categories.id', $this->storefrontContext->categoryIds())));
    }

    private function freshExpiry(): \Illuminate\Support\Carbon
    {
        return now()->addDays(self::CART_TTL_DAYS);
    }
}

