<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Catalog\Cart\CartIdentifier;
use App\Domain\Catalog\Cart\Exceptions\InsufficientStockException;
use App\Domain\Catalog\Storefront\StorefrontContext;
use App\Domain\Discount\Services\DiscountService;
use App\Domain\Order\Actions\GenerateOrderNumberAction;
use App\Domain\Order\Contracts\CheckoutRepository;
use App\Domain\Order\Entities\CheckoutResult;
use App\Domain\Shipping\Entities\ShippingAddressEntity;
use App\Infrastructure\Outbox\OutboxService;
use App\Infrastructure\Persistence\Eloquent\Mappers\Order\OrderMapper;
use App\Infrastructure\Persistence\Eloquent\Models\CartItem;
use App\Infrastructure\Persistence\Eloquent\Models\Order;
use App\Infrastructure\Persistence\Eloquent\Models\ProductVariant;
use App\Infrastructure\Persistence\Eloquent\Models\ShippingRate;
use App\Infrastructure\Persistence\Eloquent\Models\ShippingZone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class EloquentCheckoutRepository implements CheckoutRepository
{
    public function __construct(
        private readonly GenerateOrderNumberAction $generateOrderNumber,
        private readonly StorefrontContext $storefrontContext,
        private readonly OutboxService $outbox,
        private readonly DiscountService $discounts,
    ) {}

    public function createPendingOrderFromCart(
        CartIdentifier $cartIdentifier,
        ShippingAddressEntity $shippingAddress,
        array $shippingAddressPayload,
        ?array $billingAddressPayload,
        string $shippingRateId,
        string $paymentMethod,
        ?string $discountCode,
        ?int $userId,
        string $idempotencyOwner,
        string $idempotencyKey,
        string $requestHash,
    ): CheckoutResult {
        return DB::transaction(function () use (
            $cartIdentifier,
            $shippingAddress,
            $shippingAddressPayload,
            $billingAddressPayload,
            $shippingRateId,
            $discountCode,
            $userId,
            $idempotencyOwner,
            $idempotencyKey,
            $requestHash,
        ) {
            $existing = Order::query()
                ->where('idempotency_owner', $idempotencyOwner)
                ->where('idempotency_key', $idempotencyKey)
                ->with('items')
                ->first();

            if ($existing !== null) {
                if (! hash_equals((string) $existing->idempotency_request_hash, $requestHash)) {
                    throw new RuntimeException('This Idempotency-Key was already used with a different checkout request.');
                }

                return new CheckoutResult(OrderMapper::toDomain($existing), true);
            }

            $cartItems = $this->loadCartItems($cartIdentifier);

            if ($cartItems->isEmpty()) {
                throw new RuntimeException('Cart is empty.');
            }

            $subtotal = 0.0;
            $reservedItems = [];

            foreach ($cartItems->sortBy('product_id')->values() as $cartItem) {
                $product = $cartItem->product;
                $selectedVariant = $cartItem->variant ?? $product?->defaultVariant;

                if (! $product || ! $selectedVariant || $selectedVariant->product_id !== $product->id) {
                    throw new RuntimeException('One or more cart items are no longer available.');
                }

                $variant = ProductVariant::query()
                    ->whereKey($selectedVariant->id)
                    ->where('product_id', $product->id)
                    ->whereIn('status', ProductVariant::SELLABLE_STATUSES)
                    ->lockForUpdate()
                    ->first();

                if (! $variant) {
                    throw new RuntimeException('One or more selected product variants are no longer available.');
                }

                $availableQuantity = (int) $variant->stock_quantity - (int) $variant->reserved_quantity;

                if ($availableQuantity < $cartItem->quantity) {
                    throw new InsufficientStockException(
                        productName: $product->name,
                        available: max(0, $availableQuantity)
                    );
                }

                $unitPrice = $this->effectiveVariantPrice($variant);
                $lineTotal = $unitPrice * $cartItem->quantity;
                $subtotal += $lineTotal;

                $variant->increment('reserved_quantity', $cartItem->quantity);

                $reservedItems[] = [
                    'product' => $product,
                    'variant' => $variant,
                    'quantity' => (int) $cartItem->quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                    'category_id' => (string) $product->category_id,
                    'brand_id' => $product->brand_id ? (string) $product->brand_id : null,
                    'collection_ids' => $product->collections->pluck('id')->map(fn ($id) => (string) $id)->all(),
                    'is_on_sale' => $this->variantIsOnSale($variant),
                ];
            }

            $rate = $this->resolveShippingRate(
                shippingAddress: $shippingAddress,
                shippingRateId: $shippingRateId,
                cartSubtotal: $subtotal,
            );

            $shippingAmount = $this->calculateShippingAmount($rate, $subtotal);

            $discount = null;
            if ($discountCode !== null && trim($discountCode) !== '') {
                $discount = $this->discounts->calculate(
                    code: $discountCode,
                    lines: array_map(fn (array $item) => [
                        'product_id' => (string) $item['product']->id,
                        'variant_id' => (string) $item['variant']->id,
                        'category_id' => $item['category_id'],
                        'brand_id' => $item['brand_id'],
                        'collection_ids' => $item['collection_ids'],
                        'quantity' => $item['quantity'],
                        'line_subtotal' => $item['line_total'],
                        'is_on_sale' => $item['is_on_sale'],
                    ], $reservedItems),
                    shippingAmount: $shippingAmount,
                    userId: $userId,
                    guestId: $cartIdentifier->cartToken,
                    email: $shippingAddressPayload['email'] ?? null,
                    lockForUpdate: true,
                );
            }

            $discountAmount = (float) ($discount['discount_amount'] ?? 0);
            $shippingDiscountAmount = (float) ($discount['shipping_discount_amount'] ?? 0);

            $order = Order::create([
                'user_id' => $userId,
                'guest_id' => $cartIdentifier->cartToken,
                'idempotency_owner' => $idempotencyOwner,
                'idempotency_key' => $idempotencyKey,
                'idempotency_request_hash' => $requestHash,
                'order_number' => $this->generateOrderNumber->run(),
                'status' => 'pending_payment',
                'discount_code_id' => $discount['discount_code_id'] ?? null,
                'discount_code' => $discount['code'] ?? null,
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'shipping_amount' => $shippingAmount,
                'shipping_discount_amount' => $shippingDiscountAmount,
                'discount_snapshot' => $discount['snapshot'] ?? null,
                'total' => max(0, $subtotal - $discountAmount + $shippingAmount - $shippingDiscountAmount),
                'currency' => 'NGN',
                'shipping_rate_id' => $rate->id,
                'shipping_method_name' => $rate->method->name,
                'shipping_zone_name' => $rate->zone->name,
                'shipping_address' => $shippingAddressPayload,
                'billing_address' => $billingAddressPayload,
                'expires_at' => now()->addMinutes(30),
                'placed_at' => now(),
            ]);

            foreach ($reservedItems as $index => $reserved) {
                $product = $reserved['product'];
                $variant = $reserved['variant'];

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_variant_id' => $variant->id,
                    'product_name' => $product->name,
                    'product_slug' => $product->slug,
                    'sku' => $variant->sku,
                    'unit_price' => $reserved['unit_price'],
                    'quantity' => $reserved['quantity'],
                    'line_subtotal' => $reserved['line_total'],
                    'discount_amount' => $discount['line_discounts'][$index] ?? 0,
                    'line_total' => $reserved['line_total'] - ($discount['line_discounts'][$index] ?? 0),
                    'product_snapshot' => [
                        'product_id' => $product->id,
                        'variant_id' => $variant->id,
                        'name' => $product->name,
                        'slug' => $product->slug,
                        'sku' => $variant->sku,
                        'price' => $reserved['unit_price'],
                        'attributes' => $variant->attributes ?? [],
                        'images' => $this->orderItemImages($variant, $product),
                    ],
                ]);
            }

            if ($discount !== null) {
                $this->discounts->reserve($discount, $order, $userId, $cartIdentifier->cartToken);
            }

            CartItem::query()
                ->tap(fn (Builder $query) => $this->constrainCart($query, $cartIdentifier))
                ->whereKey($cartItems->modelKeys())
                ->delete();

            $this->outbox->record(
                "order:{$order->id}:placed",
                'order.placed',
                ['order_id' => (string) $order->id],
            );

            return new CheckoutResult(OrderMapper::toDomain($order->load('items')), false);
        });
    }

    private function loadCartItems(CartIdentifier $cartIdentifier)
    {
        return CartItem::query()
            ->with([
                'product' => fn ($query) => $query->withoutGlobalScopes()->with(['media', 'collections']),
                'product.defaultVariant.media',
                'variant.media',
            ])
            ->tap(fn (Builder $query) => $this->constrainCart($query, $cartIdentifier))
            ->when($this->storefrontContext->isActive(), function (Builder $query) {
                $query->whereHas('product', fn (Builder $productQuery) => $productQuery
                    ->withoutGlobalScopes()
                    ->whereIn('category_id', $this->storefrontContext->categoryIds()));
            })
            ->get();
    }

    private function constrainCart(Builder $query, CartIdentifier $cartIdentifier): void
    {
        if ($cartIdentifier->isGuest()) {
            $query->where('cart_token', $cartIdentifier->cartToken);

            return;
        }

        $query->where('user_id', $cartIdentifier->userId);
    }

    private function resolveShippingRate(
        ShippingAddressEntity $shippingAddress,
        string $shippingRateId,
        float $cartSubtotal,
    ): ShippingRate {
        $zone = $this->findBestZoneForAddress($shippingAddress);

        if (! $zone) {
            throw new RuntimeException('No shipping zone is available for this address.');
        }

        $rate = ShippingRate::query()
            ->whereKey($shippingRateId)
            ->where('shipping_zone_id', $zone->id)
            ->where('is_active', true)
            ->whereHas('method', fn ($query) => $query->where('is_active', true))
            ->with(['zone', 'method'])
            ->first();

        if (! $rate) {
            throw new RuntimeException('The selected shipping option is no longer available.');
        }

        if ($rate->min_order_amount !== null && $cartSubtotal < (float) $rate->min_order_amount) {
            throw new RuntimeException('The selected shipping option requires a higher cart subtotal.');
        }

        if ($rate->max_order_amount !== null && $cartSubtotal > (float) $rate->max_order_amount) {
            throw new RuntimeException('The selected shipping option is not available for this cart subtotal.');
        }

        return $rate;
    }

    private function findBestZoneForAddress(ShippingAddressEntity $address): ?ShippingZone
    {
        return ShippingZone::query()
            ->where('is_active', true)
            ->where('country', $address->country)
            ->where(function ($query) use ($address) {
                if ($address->state && $address->city) {
                    $query->orWhere(function ($q) use ($address) {
                        $q->where('state', $address->state)
                            ->where('city', $address->city);
                    });
                }

                if ($address->state) {
                    $query->orWhere(function ($q) use ($address) {
                        $q->where('state', $address->state)
                            ->whereNull('city');
                    });
                }

                $query->orWhere(function ($q) {
                    $q->whereNull('state')
                        ->whereNull('city');
                });
            })
            ->orderByRaw(
                '
                CASE
                    WHEN state = ? AND city = ? THEN 1
                    WHEN state = ? AND city IS NULL THEN 2
                    WHEN state IS NULL AND city IS NULL THEN 3
                    ELSE 4
                END
                ',
                [
                    $address->state,
                    $address->city,
                    $address->state,
                ]
            )
            ->first();
    }

    private function calculateShippingAmount(ShippingRate $rate, float $cartSubtotal): float
    {
        if (
            $rate->free_over_amount !== null &&
            $cartSubtotal >= (float) $rate->free_over_amount
        ) {
            return 0.0;
        }

        return (float) $rate->amount;
    }

    private function effectiveVariantPrice(ProductVariant $variant): float
    {
        if ($this->variantIsOnSale($variant)) {
            return (float) $variant->sale_price;
        }

        return (float) $variant->price;
    }

    private function variantIsOnSale(ProductVariant $variant): bool
    {
        return $variant->sale_price !== null
            && ($variant->sale_starts_at === null || now()->greaterThanOrEqualTo($variant->sale_starts_at))
            && ($variant->sale_ends_at === null || now()->lessThanOrEqualTo($variant->sale_ends_at));
    }

    private function orderItemImages(ProductVariant $variant, $product): array
    {
        $variantImages = $variant->getMedia('catalog-photos')
            ->map(fn ($media) => [
                'id' => $media->id,
                'name' => $media->name,
                'url' => $media->getUrl(),
                'thumb' => $media->getUrl('thumb'),
                'medium' => $media->getUrl('medium'),
            ])
            ->all();

        if ($variantImages !== []) {
            return $variantImages;
        }

        return $product->getMedia('catalog-photos')
            ->map(fn ($media) => [
                'id' => $media->id,
                'name' => $media->name,
                'url' => $media->getUrl(),
                'thumb' => $media->getUrl('thumb'),
                'medium' => $media->getUrl('medium'),
            ])
            ->all();
    }
}
