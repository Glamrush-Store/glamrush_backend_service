<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Payment\Contracts\PaymentMethodRepository;
use App\Domain\Payment\Entities\PaymentMethodEntity;
use App\Infrastructure\Caching\CacheTags;
use App\Infrastructure\Caching\QueryCache;
use App\Infrastructure\Persistence\Eloquent\Mappers\Payment\PaymentMethodMapper;
use App\Infrastructure\Persistence\Eloquent\Models\PaymentMethod;

final class EloquentPaymentMethodRepository implements PaymentMethodRepository
{
    public function active(): array
    {
        return QueryCache::rememberTagged(
            'payment-methods:active:v1',
            [CacheTags::PAYMENT_METHODS],
            (int) config('api_cache.payment_methods_ttl', 600),
            fn (): array => PaymentMethod::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->map(fn (PaymentMethod $method): PaymentMethodEntity => PaymentMethodMapper::toDomain($method))
                ->all(),
        );
    }

    public function findActiveByCode(string $code): ?PaymentMethodEntity
    {
        return collect($this->active())->first(
            fn (PaymentMethodEntity $method): bool => $method->code === $code,
        );
    }
}
