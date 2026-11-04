<?php

namespace App\Infrastructure\Persistence\Eloquent\Mappers\Address;

use App\Domain\User\Entities\AddressEntity;
use App\Infrastructure\Persistence\Eloquent\Models\CustomerAddress;

final class AddressMapper
{
    public static function toDomain(CustomerAddress $model): AddressEntity
    {
        return new AddressEntity(
            id: (string)$model->id,
            userId: (string)$model->user_id,
            label: $model->label,
            firstName: $model->first_name,
            lastName: $model->last_name,
            phone: $model->phone,
            addressLine1: $model->address_line_1,
            addressLine2: $model->address_line_2,
            country: $model->country,
            state: $model->state,
            city: $model->city,
            postalCode: $model->postal_code,
            isDefault: (bool)$model->is_default,
        );
    }
}
