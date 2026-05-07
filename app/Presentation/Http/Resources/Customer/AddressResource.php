<?php

namespace App\Presentation\Http\Resources\Customer;

use App\Domain\User\Entities\AddressEntity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AddressEntity */
class AddressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'label'          => $this->label,
            'first_name'     => $this->firstName,
            'last_name'      => $this->lastName,
            'phone'          => $this->phone,
            'address_line_1' => $this->addressLine1,
            'address_line_2' => $this->addressLine2,
            'country'        => $this->country,
            'state'          => $this->state,
            'city'           => $this->city,
            'postal_code'    => $this->postalCode,
            'is_default'     => $this->isDefault,
        ];
    }
}
