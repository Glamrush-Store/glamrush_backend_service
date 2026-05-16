<?php

namespace App\Presentation\Http\Controllers\Payment;

use App\Domain\Payment\Services\PaymentService;
use App\Presentation\Http\Requests\Payment\InitializePaymentRequest;
use App\Presentation\Http\Resources\Payment\PaymentInitializationResource;
use App\Presentation\Http\Responses\ApiResponse;
use RuntimeException;

final class InitializePaymentController
{
    public function __construct(
        private readonly PaymentService $payments,
    ) {
    }

    public function __invoke(InitializePaymentRequest $request)
    {
        try {
            $payment = $this->payments->initialize(
                orderId: $request->validated('order_id'),
                paymentMethodCode: $request->validated('payment_method'),
            );
        } catch (RuntimeException $exception) {
            return ApiResponse::error($exception->getMessage(), status: 422);
        }

        return ApiResponse::success(
            new PaymentInitializationResource($payment),
            'Payment initialized.',
            201,
        );
    }
}
