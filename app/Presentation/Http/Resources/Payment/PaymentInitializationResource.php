<?php

namespace App\Presentation\Http\Resources\Payment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PaymentInitializationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'payment' => [
                'id' => $this->payment->id,
                'order_id' => $this->payment->orderId,
                'provider' => $this->payment->provider,
                'reference' => $this->payment->reference,
                'transaction_id' => $this->payment->transactionId,
                'amount' => $this->payment->amount,
                'currency' => $this->payment->currency,
                'status' => $this->payment->status,
            ],
            'authorization_url' => $this->authorizationUrl,
            'access_code' => $this->accessCode,
            'reference' => $this->reference,
            'provider' => $this->provider,
            'status' => $this->status,
            'next_actions' => [
                'retry_payment' => in_array($this->status, ['failed', 'pending', 'initialized'], true),
                'restore_cart' => false,
                'retry_endpoint' => '/api/v1/payments/initialize',
            ],
        ];
    }
}
