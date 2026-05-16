<?php

namespace App\Infrastructure\Payment\Gateways;

use App\Domain\Order\Entities\OrderEntity;
use App\Domain\Payment\Contracts\PaymentGateway;
use App\Domain\Payment\Entities\PaymentEntity;
use App\Domain\Payment\Entities\PaymentInitializationEntity;
use App\Domain\Payment\Entities\PaymentMethodEntity;
use App\Domain\Payment\Entities\PaymentVerificationEntity;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Infrastructure\Payment\Flutterwave\FlutterwaveClient;
use RuntimeException;

final class FlutterwavePaymentGateway implements PaymentGateway
{
    public function __construct(
        private readonly FlutterwaveClient $client,
    ) {
    }

    public function code(): string
    {
        return 'flutterwave';
    }

    public function initialize(OrderEntity $order, PaymentEntity $payment, PaymentMethodEntity $method): PaymentInitializationEntity
    {
        $response = $this->client->initializePayment([
            'tx_ref' => $payment->reference,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'redirect_url' => config('services.flutterwave.callback_url'),
            'customer' => [
                'email' => $order->shippingAddress['email'] ?? config('mail.from.address'),
                'name' => $order->shippingAddress['full_name'] ?? null,
                'phonenumber' => $order->shippingAddress['phone'] ?? null,
            ],
            'customizations' => [
                'title' => config('app.name'),
                'description' => "Order {$order->orderNumber}",
            ],
            'meta' => [
                'order_id' => $order->id,
                'order_number' => $order->orderNumber,
                'payment_id' => $payment->id,
            ],
        ]);

        if (($response['status'] ?? null) !== 'success') {
            throw new RuntimeException($response['message'] ?? 'Unable to initialize Flutterwave payment.');
        }

        return new PaymentInitializationEntity(
            payment: $payment,
            authorizationUrl: $response['data']['link'] ?? null,
            accessCode: null,
            reference: $payment->reference,
            provider: $this->code(),
            status: PaymentStatus::INITIALIZED->value,
        );
    }

    public function verify(string $transactionId): PaymentVerificationEntity
    {
        $response = $this->client->verifyTransaction($transactionId);

        if (($response['status'] ?? null) !== 'success') {
            throw new RuntimeException($response['message'] ?? 'Unable to verify Flutterwave payment.');
        }

        $data = $this->verifiedTransactionData($response);

        return new PaymentVerificationEntity(
            reference: $data['tx_ref'] ?? '',
            transactionId: isset($data['id']) ? (string) $data['id'] : $transactionId,
            providerReference: $data['flw_ref'] ?? null,
            status: $data['status'] ?? 'failed',
            amount: isset($data['amount']) ? (float) $data['amount'] : null,
            currency: $data['currency'] ?? null,
            payload: $response,
        );
    }

    public function webhookIsValid(string $rawPayload, ?string $signature): bool
    {
        return $this->client->verifyWebhookSignature($signature);
    }

    public function transactionIdFromWebhook(array $payload): ?string
    {
        $id = $payload['data']['id'] ?? $payload['id'] ?? null;

        return $id !== null ? (string) $id : null;
    }

    private function verifiedTransactionData(array $response): array
    {
        $data = $response['data'] ?? [];

        if (array_is_list($data)) {
            return $data[0] ?? [];
        }

        return is_array($data) ? $data : [];
    }
}
