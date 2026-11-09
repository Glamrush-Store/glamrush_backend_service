<?php

namespace App\Infrastructure\Payment\Gateways;

use App\Domain\Order\Entities\OrderEntity;
use App\Domain\Payment\Contracts\PaymentGateway;
use App\Domain\Payment\Entities\PaymentEntity;
use App\Domain\Payment\Entities\PaymentInitializationEntity;
use App\Domain\Payment\Entities\PaymentMethodEntity;
use App\Domain\Payment\Entities\PaymentVerificationEntity;
use App\Domain\Payment\Enums\PaymentStatus;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class BachsPaymentGateway implements PaymentGateway
{
    public function code(): string
    {
        return 'bachs';
    }

    public function initialize(OrderEntity $order, PaymentEntity $payment, PaymentMethodEntity $method): PaymentInitializationEntity
    {
        $customer = array_filter([
            'email' => $order->shippingAddress['email'] ?? null,
            'name' => $order->shippingAddress['full_name'] ?? null,
            'phone_number' => $order->shippingAddress['phone'] ?? null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        $response = Http::withToken($this->apiKey())
            ->acceptJson()
            ->withHeaders(['Idempotency-Key' => $payment->reference])
            ->post($this->baseUrl().'/v1/checkout-sessions', [
                'pricing' => [
                    'currency' => $payment->currency,
                    'amount' => number_format($payment->amount, 2, '.', ''),
                ],
                'customer' => $customer,
                'success_url' => $this->callbackUrl(),
                'cancel_url' => config('services.bachs.cancel_url'),
                'reference' => $payment->reference,
                'metadata' => [
                    'order_id' => $order->id,
                    'order_number' => $order->orderNumber,
                    'payment_id' => $payment->id,
                ],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException($this->errorMessage($response, 'Unable to initialize Bachs payment.'));
        }

        $checkoutId = $response->json('checkout_id');
        $checkoutUrl = $response->json('checkout_url');

        if (! is_string($checkoutId) || $checkoutId === '' || ! is_string($checkoutUrl) || $checkoutUrl === '') {
            throw new RuntimeException('Bachs returned an invalid checkout session.');
        }

        return new PaymentInitializationEntity(
            payment: $payment,
            authorizationUrl: $checkoutUrl,
            accessCode: $checkoutId,
            reference: $payment->reference,
            provider: $this->code(),
            status: PaymentStatus::INITIALIZED->value,
        );
    }

    public function verify(string $transactionId): PaymentVerificationEntity
    {
        $checkoutId = rawurlencode($transactionId);
        $response = Http::withToken($this->apiKey())
            ->acceptJson()
            ->get($this->baseUrl()."/v1/checkout-sessions/{$checkoutId}");

        if (! $response->successful()) {
            throw new RuntimeException($this->errorMessage($response, 'Unable to verify Bachs payment.'));
        }

        $data = $response->json();
        $status = $data['payment_status'] ?? $data['status'] ?? 'failed';

        if ($status === 'succeeded' || $status === 'completed') {
            $status = 'success';
        }

        $providerReference = $data['charge']['charge_id']
            ?? $data['charge']['id']
            ?? $data['checkout_id']
            ?? $transactionId;

        return new PaymentVerificationEntity(
            reference: (string) ($data['reference'] ?? ''),
            transactionId: (string) $providerReference,
            providerReference: (string) $providerReference,
            status: (string) $status,
            amount: isset($data['amount']) ? (float) $data['amount'] : null,
            currency: isset($data['currency']) ? (string) $data['currency'] : null,
            payload: $data,
        );
    }

    public function webhookIsValid(string $rawPayload, ?string $signature): bool
    {
        if ($signature === null || $signature === '') {
            return false;
        }

        $parts = explode(',', $signature);
        $timestamp = null;
        $signatures = [];

        foreach ($parts as $part) {
            [$name, $value] = array_pad(explode('=', trim($part), 2), 2, null);

            if ($name === 't') {
                $timestamp = $value;
            } elseif ($name === 'v1' && is_string($value) && $value !== '') {
                $signatures[] = $value;
            }
        }

        if (! is_string($timestamp) || ! ctype_digit($timestamp) || $signatures === []) {
            return false;
        }

        $tolerance = (int) config('services.bachs.webhook_tolerance_seconds', 300);

        if (abs(now()->timestamp - (int) $timestamp) > $tolerance) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$rawPayload, $this->webhookSecret());

        foreach ($signatures as $candidate) {
            if (hash_equals($expected, $candidate)) {
                return true;
            }
        }

        return false;
    }

    public function transactionIdFromWebhook(array $payload): ?string
    {
        $checkoutId = $payload['data']['checkout_id']
            ?? $payload['data']['checkout']['checkout_id']
            ?? null;

        return is_string($checkoutId) && $checkoutId !== '' ? $checkoutId : null;
    }

    private function apiKey(): string
    {
        return $this->requiredConfig('api_key', 'Bachs API key is not configured.');
    }

    private function webhookSecret(): string
    {
        return $this->requiredConfig('webhook_secret', 'Bachs webhook secret is not configured.');
    }

    private function callbackUrl(): string
    {
        return $this->requiredConfig('callback_url', 'Bachs callback URL is not configured.');
    }

    private function baseUrl(): string
    {
        return rtrim($this->requiredConfig('base_url', 'Bachs base URL is not configured.'), '/');
    }

    private function requiredConfig(string $key, string $message): string
    {
        $value = config("services.bachs.{$key}");

        if (! is_string($value) || $value === '') {
            throw new RuntimeException($message);
        }

        return $value;
    }

    private function errorMessage(Response $response, string $fallback): string
    {
        $detail = $response->json('detail');

        return is_string($detail) && $detail !== '' ? $detail : $fallback;
    }
}
