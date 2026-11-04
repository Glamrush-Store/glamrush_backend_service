<?php

namespace App\Presentation\Http\Controllers\Payment;

use App\Domain\Payment\Services\PaymentMethodService;
use App\Presentation\Http\Resources\Payment\PaymentMethodResource;
use App\Presentation\Http\Responses\ApiResponse;

final class ListPaymentMethodsController
{
    public function __construct(
        private readonly PaymentMethodService $paymentMethods,
    ) {
    }

    public function __invoke()
    {
        return ApiResponse::success(
            PaymentMethodResource::collection($this->paymentMethods->active())
        );
    }
}
