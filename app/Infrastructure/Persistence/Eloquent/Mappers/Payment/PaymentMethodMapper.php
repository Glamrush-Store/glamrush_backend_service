<?php

namespace App\Infrastructure\Persistence\Eloquent\Mappers\Payment;

use App\Domain\Payment\Entities\PaymentMethodEntity;
use App\Infrastructure\Persistence\Eloquent\Models\PaymentMethod;

final class PaymentMethodMapper
{
    public static function toDomain(PaymentMethod $model): PaymentMethodEntity
    {
        return new PaymentMethodEntity(
            id: (string) $model->id,
            name: $model->name,
            code: $model->code,
            description: $model->description,
            isActive: (bool) $model->is_active,
            sortOrder: (int) $model->sort_order,
            publicConfig: $model->public_config ?? [],
        );
    }
}
