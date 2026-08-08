<?php

namespace App\Domain\Discount\Services;

use App\Domain\Catalog\Cart\CartIdentifier;
use App\Domain\Catalog\Storefront\StorefrontContext;
use App\Infrastructure\Persistence\Eloquent\Models\CartItem;
use App\Infrastructure\Persistence\Eloquent\Models\DiscountCode;
use App\Infrastructure\Persistence\Eloquent\Models\DiscountRedemption;
use App\Infrastructure\Persistence\Eloquent\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class DiscountService
{
    public function __construct(private readonly StorefrontContext $storefrontContext) {}

    public function preview(
        CartIdentifier $cartIdentifier,
        string $code,
        float $shippingAmount,
        ?int $userId,
        ?string $email,
    ): array {
        $items = CartItem::query()
            ->with([
                'product' => fn ($query) => $query->withoutGlobalScopes()->with('collections'),
                'product.defaultVariant',
                'variant',
            ])
            ->tap(fn (Builder $query) => $this->constrainCart($query, $cartIdentifier))
            ->whereHas('product', fn (Builder $query) => $query->withoutGlobalScopes()
                ->whereIn('category_id', $this->storefrontContext->categoryIds()))
            ->get();

        if ($items->isEmpty()) {
            throw new RuntimeException('Your bag is empty.');
        }

        $lines = $items->map(function (CartItem $item): array {
            $variant = $item->variant ?? $item->product?->defaultVariant;
            if (! $item->product || ! $variant) {
                throw new RuntimeException('One or more bag items are no longer available.');
            }
            $price = $this->effectivePrice($variant);

            return [
                'product_id' => (string) $item->product->id,
                'variant_id' => (string) $variant->id,
                'category_id' => (string) $item->product->category_id,
                'brand_id' => $item->product->brand_id ? (string) $item->product->brand_id : null,
                'collection_ids' => $item->product->collections->pluck('id')->map(fn ($id) => (string) $id)->all(),
                'quantity' => (int) $item->quantity,
                'line_subtotal' => $price * (int) $item->quantity,
                'is_on_sale' => $this->isOnSale($variant),
            ];
        })->all();

        return $this->calculate($code, $lines, $shippingAmount, $userId, $cartIdentifier->cartToken, $email, false);
    }

    /** @param list<array<string, mixed>> $lines */
    public function calculate(
        string $code,
        array $lines,
        float $shippingAmount,
        ?int $userId,
        ?string $guestId,
        ?string $email,
        bool $lockForUpdate,
    ): array {
        $normalizedCode = strtoupper(trim($code));
        $query = DiscountCode::query()->with(['targets', 'storefronts:id']);
        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        $discount = $query->where('code', $normalizedCode)->first();
        if (! $discount || ! $discount->is_active) {
            throw new RuntimeException('This discount code is not valid.');
        }
        if ($discount->starts_at?->isFuture()) {
            throw new RuntimeException('This discount code is not active yet.');
        }
        if ($discount->ends_at !== null && $discount->ends_at->lessThanOrEqualTo(now())) {
            throw new RuntimeException('This discount code has expired.');
        }

        $rootCategoryId = $this->storefrontContext->rootCategoryId();
        if (! $discount->applies_to_all_storefronts
            && (! $rootCategoryId || ! $discount->storefronts->contains('id', $rootCategoryId))) {
            throw new RuntimeException('This discount code is not available on this storefront.');
        }
        if ($discount->type === 'fixed_amount' && strtoupper((string) $discount->currency) !== 'NGN') {
            throw new RuntimeException('This discount code is not available for the checkout currency.');
        }

        $customerKey = $this->customerKey($userId, $email);
        if (($discount->per_customer_usage_limit || $discount->first_order_only) && $customerKey === null) {
            throw new RuntimeException('Enter your checkout email before applying this discount code.');
        }

        $this->assertUsageAvailable($discount, $customerKey);
        if ($discount->first_order_only && $this->hasPreviousOrder($userId, $email)) {
            throw new RuntimeException('This discount code is only available on a first order.');
        }

        $subtotalCents = array_sum(array_map(fn (array $line) => $this->toCents($line['line_subtotal']), $lines));
        if ($discount->minimum_subtotal !== null && $subtotalCents < $this->toCents($discount->minimum_subtotal)) {
            throw new RuntimeException('Your bag does not meet the minimum subtotal for this discount code.');
        }

        $targets = $discount->targets;
        $includeTargets = $targets->where('mode', 'include');
        $excludeTargets = $targets->where('mode', 'exclude');
        $eligibleIndexes = [];
        $eligibleSubtotalCents = 0;
        foreach ($lines as $index => $line) {
            if (! $discount->applies_to_sale_items && ($line['is_on_sale'] ?? false)) {
                continue;
            }
            $included = $includeTargets->isEmpty() || $includeTargets->contains(fn ($target) => $this->targetMatches($target, $line));
            $excluded = $excludeTargets->contains(fn ($target) => $this->targetMatches($target, $line));
            if ($included && ! $excluded) {
                $eligibleIndexes[] = $index;
                $eligibleSubtotalCents += $this->toCents($line['line_subtotal']);
            }
        }

        if ($eligibleSubtotalCents === 0) {
            throw new RuntimeException('This discount code does not apply to the items in your bag.');
        }

        $discountCents = match ($discount->type) {
            'percentage' => (int) round($eligibleSubtotalCents * (float) $discount->value / 100),
            'fixed_amount' => min($eligibleSubtotalCents, $this->toCents($discount->value)),
            'free_shipping' => 0,
            default => throw new RuntimeException('This discount code type is not supported.'),
        };
        if ($discount->type === 'percentage' && $discount->maximum_discount_amount !== null) {
            $discountCents = min($discountCents, $this->toCents($discount->maximum_discount_amount));
        }
        $shippingCents = max(0, $this->toCents($shippingAmount));
        $shippingDiscountCents = $discount->type === 'free_shipping' ? $shippingCents : 0;
        $lineDiscounts = $this->allocateLineDiscount($lines, $eligibleIndexes, $eligibleSubtotalCents, $discountCents);

        return [
            'discount_code_id' => (string) $discount->id,
            'code' => $discount->code,
            'name' => $discount->name,
            'type' => $discount->type,
            'currency' => 'NGN',
            'subtotal' => $this->fromCents($subtotalCents),
            'eligible_subtotal' => $this->fromCents($eligibleSubtotalCents),
            'discount_amount' => $this->fromCents($discountCents),
            'shipping_amount' => $this->fromCents($shippingCents),
            'shipping_discount_amount' => $this->fromCents($shippingDiscountCents),
            'total' => $this->fromCents(max(0, $subtotalCents - $discountCents + $shippingCents - $shippingDiscountCents)),
            'line_discounts' => $lineDiscounts,
            'customer_key' => $customerKey ?? 'guest:'.hash('sha256', (string) $guestId),
            'snapshot' => [
                'id' => (string) $discount->id,
                'code' => $discount->code,
                'name' => $discount->name,
                'type' => $discount->type,
                'value' => $discount->value,
                'currency' => $discount->currency,
                'maximum_discount_amount' => $discount->maximum_discount_amount,
                'minimum_subtotal' => $discount->minimum_subtotal,
            ],
        ];
    }

    public function reserve(array $quote, Order $order, ?int $userId, ?string $guestId): void
    {
        DiscountRedemption::query()->create([
            'discount_code_id' => $quote['discount_code_id'],
            'order_id' => $order->id,
            'user_id' => $userId,
            'guest_id' => $guestId,
            'customer_key' => $quote['customer_key'],
            'code' => $quote['code'],
            'type' => $quote['type'],
            'discount_amount' => $quote['discount_amount'],
            'shipping_discount_amount' => $quote['shipping_discount_amount'],
            'currency' => $quote['currency'],
            'status' => 'reserved',
            'snapshot' => $quote['snapshot'],
            'reserved_at' => now(),
            'expires_at' => $order->expires_at,
        ]);
    }

    private function assertUsageAvailable(DiscountCode $discount, ?string $customerKey): void
    {
        $active = fn (Builder $query) => $query->where(function (Builder $q) {
            $q->where('status', 'redeemed')->orWhere(fn (Builder $reserved) => $reserved
                ->where('status', 'reserved')->where('expires_at', '>', now()));
        });
        if ($discount->total_usage_limit !== null
            && DiscountRedemption::query()->where('discount_code_id', $discount->id)->tap($active)->count() >= $discount->total_usage_limit) {
            throw new RuntimeException('This discount code has reached its usage limit.');
        }
        if ($discount->per_customer_usage_limit !== null && $customerKey !== null
            && DiscountRedemption::query()->where('discount_code_id', $discount->id)->where('customer_key', $customerKey)->tap($active)->count() >= $discount->per_customer_usage_limit) {
            throw new RuntimeException('You have already used this discount code the maximum number of times.');
        }
    }

    private function hasPreviousOrder(?int $userId, ?string $email): bool
    {
        return Order::query()
            ->where(function (Builder $query) {
                $query->whereIn('status', ['pending_on_delivery', 'paid', 'processing', 'shipped', 'completed'])
                    ->orWhere(fn (Builder $pending) => $pending
                        ->where('status', 'pending_payment')
                        ->where('expires_at', '>', now()));
            })
            ->when($userId !== null,
                fn (Builder $query) => $query->where('user_id', $userId),
                fn (Builder $query) => $query->where('shipping_address->email', strtolower(trim((string) $email))))
            ->exists();
    }

    private function targetMatches(object $target, array $line): bool
    {
        return match ($target->target_type) {
            'product' => $line['product_id'] === $target->target_id,
            'product_variant' => $line['variant_id'] === $target->target_id,
            'brand' => $line['brand_id'] === $target->target_id,
            'collection' => in_array($target->target_id, $line['collection_ids'], true),
            'category' => in_array($target->target_id, $this->categoryLineage($line['category_id']), true),
            default => false,
        };
    }

    /** @return list<string> */
    private function categoryLineage(string $categoryId): array
    {
        static $cache = [];
        if (isset($cache[$categoryId])) return $cache[$categoryId];
        $ids = [];
        $current = $categoryId;
        while ($current !== null) {
            $ids[] = $current;
            $current = DB::table('categories')->where('id', $current)->value('parent_id');
        }
        return $cache[$categoryId] = $ids;
    }

    private function allocateLineDiscount(array $lines, array $eligibleIndexes, int $eligibleSubtotal, int $discount): array
    {
        $result = array_fill(0, count($lines), 0.0);
        $remaining = $discount;
        foreach ($eligibleIndexes as $position => $index) {
            $amount = $position === array_key_last($eligibleIndexes)
                ? $remaining
                : (int) floor($discount * $this->toCents($lines[$index]['line_subtotal']) / $eligibleSubtotal);
            $result[$index] = $this->fromCents($amount);
            $remaining -= $amount;
        }
        return $result;
    }

    private function customerKey(?int $userId, ?string $email): ?string
    {
        if ($userId !== null) return 'user:'.$userId;
        $email = strtolower(trim((string) $email));
        return $email === '' ? null : 'email:'.hash('sha256', $email);
    }

    private function constrainCart(Builder $query, CartIdentifier $identifier): void
    {
        $identifier->isGuest() ? $query->where('cart_token', $identifier->cartToken) : $query->where('user_id', $identifier->userId);
    }

    private function effectivePrice(object $variant): float
    {
        return $this->isOnSale($variant) ? (float) $variant->sale_price : (float) $variant->price;
    }

    private function isOnSale(object $variant): bool
    {
        return $variant->sale_price !== null
            && ($variant->sale_starts_at === null || now()->greaterThanOrEqualTo($variant->sale_starts_at))
            && ($variant->sale_ends_at === null || now()->lessThanOrEqualTo($variant->sale_ends_at));
    }

    private function toCents(mixed $amount): int { return (int) round((float) $amount * 100); }
    private function fromCents(int $amount): float { return $amount / 100; }
}
