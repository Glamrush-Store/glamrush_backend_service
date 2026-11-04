<?php

namespace App\Presentation\Http\Controllers\Payment;

use App\Domain\Payment\Services\PaymentService;
use App\Presentation\Http\Requests\Payment\VerifyPaymentRequest;
use App\Presentation\Http\Resources\Payment\PaymentInitializationResource;
use App\Presentation\Http\Responses\ApiResponse;
use RuntimeException;

final class VerifyPaymentController
{
    public function __construct(
        private readonly PaymentService $payments,
    ) {
    }

    public function __invoke(VerifyPaymentRequest $request)
    {
        try {
            $payment = $this->payments->verify(
                provider: $request->validated('provider'),
                transactionId: $request->validated('transaction_id'),
            );
        } catch (RuntimeException $exception) {
            return ApiResponse::error($exception->getMessage(), status: 422);
        }

        return ApiResponse::success(new PaymentInitializationResource($payment));
    }
}
