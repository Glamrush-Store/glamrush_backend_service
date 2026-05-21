<?php
/*
 * © 2026 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Domain\Shipping\Services;

use App\Domain\Shipping\Contracts\ShippingRepository;
use App\Domain\Shipping\Entities\ShippingAddressEntity;
use App\Domain\Shipping\Entities\ShippingOptionEntity;


class ShippingQuoteService
{

    public function __construct(
        private readonly ShippingRepository $shippingRepository,
    ) {
    }


    /**
     * @return ShippingOptionEntity[]
     */
    public function getShippingOptions(
        ShippingAddressEntity $address,
        float $cartSubtotal
    ): array {
        $zone = $this->shippingRepository->findBestZoneForAddress($address);

        if (!$zone) {
            return [];
        }

        $rates = $this->shippingRepository->getActiveRatesForZone($zone->id);


        return array_values(
            array_filter(
                array_map(
                    function ($rate) use ($zone, $cartSubtotal) {
                        if (
                            $rate->minOrderAmount !== null &&
                            $cartSubtotal < $rate->minOrderAmount
                        ) {
                            return null;
                        }

                        if (
                            $rate->maxOrderAmount !== null &&
                            $cartSubtotal > $rate->maxOrderAmount
                        ) {
                            return null;
                        }

                        $amount = $rate->amount;

                        if (
                            $rate->freeOverAmount !== null &&
                            $cartSubtotal >= $rate->freeOverAmount
                        ) {
                            $amount = 0.0;
                        }

                        return new ShippingOptionEntity(
                            rateId: $rate->id,
                            method: $rate->shippingMethod,
                            zone: $rate->shippingZone,
                            amount: $amount,
                            currency: 'NGN',
                            estimatedDaysMin: $rate->estimatedDaysMin,
                            estimatedDaysMax: $rate->estimatedDaysMax,
                        );
                    },
                    $rates
                )
            )
        );
    }

}
