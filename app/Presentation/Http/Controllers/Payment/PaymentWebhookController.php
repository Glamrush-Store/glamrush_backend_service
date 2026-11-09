<?php

namespace App\Presentation\Http\Controllers\Payment;

use App\Domain\Payment\Services\PaymentService;
use App\Presentation\Http\Responses\ApiResponse;
use Illuminate\Http\Request;
use RuntimeException;

final class PaymentWebhookController
{
    public function __construct(
        private readonly PaymentService $payments,
    ) {}

    public function __invoke(string $provider, Request $request)
    {
        $signature = match ($provider) {
            'bachs' => $request->header('x-bachs-signature-v2')
                ?? $this->legacyBachsSignature($request),
            'paystack' => $request->header('x-paystack-signature'),
            'flutterwave' => $request->header('verif-hash'),
            default => null,
        };

        try {
            $this->payments->handleWebhook(
                provider: $provider,
                rawPayload: $request->getContent(),
                signature: $signature,
            );
        } catch (RuntimeException $exception) {
            return ApiResponse::error($exception->getMessage(), status: 400);
        }

        return ApiResponse::success(null, 'Webhook processed.');
    }

    private function legacyBachsSignature(Request $request): ?string
    {
        $timestamp = $request->header('x-bachs-timestamp');
        $signature = $request->header('x-bachs-signature');

        if ($timestamp === null || $signature === null) {
            return null;
        }

        return "t={$timestamp},v1={$signature}";
    }
}
