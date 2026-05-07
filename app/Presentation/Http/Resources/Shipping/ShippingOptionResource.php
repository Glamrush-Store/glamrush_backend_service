<?php
/*
 * © 2026 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */


namespace App\Presentation\Http\Resources\Shipping;


use App\Domain\Shipping\Entities\ShippingOptionEntity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ShippingOptionEntity */
class ShippingOptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'rate_id' => $this->rateId,
            'method' => $this->method,
            'zone' => $this->zone,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'estimated_days_min' => $this->estimatedDaysMin,
            'estimated_days_max' => $this->estimatedDaysMax,
        ];
    }
}
