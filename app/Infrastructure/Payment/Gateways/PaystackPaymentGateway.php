<?php

namespace App\Infrastructure\Payment\Gateways;

use App\Domain\Order\Entities\OrderEntity;
use App\Domain\Payment\Contracts\PaymentGateway;
use App\Domain\Payment\Entities\PaymentEntity;
use App\Domain\Payment\Entities\PaymentInitializationEntity;
use App\Domain\Payment\Entities\PaymentMethodEntity;
use App\Domain\Payment\Entities\PaymentVerificationEntity;
use App\Domain\Payment\Enums\PaymentStatus;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class PaystackPaymentGateway implements PaymentGateway
{
    public function code(): string
    {
        return 'paystack';
    }

    public function initialize(OrderEntity $order, PaymentEntity $payment, PaymentMethodEntity $method): PaymentInitializationEntity
    {
        $response = Http::withToken($this->secretKey())
            ->post('https://api.paystack.co/transaction/initialize', [
                'amount' => (int) round($payment->amount * 100),
                'email' => $order->shippingAddress['email'] ?? config('mail.from.address'),
                'currency' => $payment->currency,
                'reference' => $payment->reference,
                'callback_url' => config('services.paystack.callback_url'),
                'metadata' => [
                    'order_id' => $order->id,
                    'order_number' => $order->orderNumber,
                    'payment_id' => $payment->id,
                ],
            ]);

        if (! $response->successful() || ! $response->json('status')) {
            throw new RuntimeException($response->json('message') ?? 'Unable to initialize Paystack payment.');
        }

        return new PaymentInitializationEntity(
            payment: $payment,
            authorizationUrl: $response->json('data.authorization_url'),
            accessCode: $response->json('data.access_code'),
            reference: $payment->reference,
            provider: $this->code(),
            status: PaymentStatus::INITIALIZED->value,
        );
    }

    public function verify(string $transactionId): PaymentVerificationEntity
    {
        $response = Http::withToken($this->secretKey())
            ->get("https://api.paystack.co/transaction/{$transactionId}");

        if (! $response->successful() || ! $response->json('status')) {
            throw new RuntimeException($response->json('message') ?? 'Unable to verify Paystack payment.');
        }

        return new PaymentVerificationEntity(
            reference: $response->json('data.reference', ''),
            transactionId: (string) $response->json('data.id', $transactionId),
            providerReference: $response->json('data.reference'),
            status: $response->json('data.status', 'failed'),
            amount: $response->json('data.amount') !== null ? ((float) $response->json('data.amount')) / 100 : null,
            currency: $response->json('data.currency'),
            payload: $response->json(),
        );
    }

    public function webhookIsValid(string $rawPayload, ?string $signature): bool
    {
        if ($signature === null) {
            return false;
        }

        return hash_equals(
            hash_hmac('sha512', $rawPayload, $this->secretKey()),
            $signature,
        );
    }

    public function transactionIdFromWebhook(array $payload): ?string
    {
        $id = $payload['data']['id'] ?? null;

        return $id !== null ? (string) $id : null;
    }

    private function secretKey(): string
    {
        $key = config('services.paystack.secret_key');

        if (! is_string($key) || $key === '') {
            throw new RuntimeException('Paystack secret key is not configured.');
        }

        return $key;
    }
}
