<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Payment\Contracts\PaymentMethodRepository;
use App\Domain\Payment\Entities\PaymentMethodEntity;
use App\Infrastructure\Persistence\Eloquent\Mappers\Payment\PaymentMethodMapper;
use App\Infrastructure\Persistence\Eloquent\Models\PaymentMethod;

final class EloquentPaymentMethodRepository implements PaymentMethodRepository
{
    public function active(): array
    {
        return PaymentMethod::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (PaymentMethod $method): PaymentMethodEntity => PaymentMethodMapper::toDomain($method))
            ->all();
    }

    public function findActiveByCode(string $code): ?PaymentMethodEntity
    {
        $method = PaymentMethod::query()
            ->where('is_active', true)
            ->where('code', $code)
            ->first();

        return $method ? PaymentMethodMapper::toDomain($method) : null;
    }
}
