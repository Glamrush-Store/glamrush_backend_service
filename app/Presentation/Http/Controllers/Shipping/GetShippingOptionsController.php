<?php
/*
 * © 2026 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */


namespace App\Presentation\Http\Controllers\Shipping;

use App\Domain\Shipping\Entities\ShippingAddressEntity;
use App\Domain\Shipping\Services\ShippingQuoteService;
use App\Presentation\Http\Requests\Shipping\GetShippingOptionsRequest;
use App\Presentation\Http\Resources\Shipping\ShippingOptionResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;


class GetShippingOptionsController extends Controller
{

    public function __construct(private ShippingQuoteService $shippingQuoteService)
    {
    }

    public function __invoke(
        GetShippingOptionsRequest $request,
    ): AnonymousResourceCollection {
        $address = new ShippingAddressEntity(
            country: $request->string('country'),
            state: $request->string('state')->toString(),
            city: $request->string('city')->toString(),
            postalCode: $request->filled('postal_code')
                ? $request->string('postal_code')->toString()
                : null,
        );

        $options = $this->shippingQuoteService->getShippingOptions($address, 10, 000);

        return ShippingOptionResource::collection($options);
    }

}


