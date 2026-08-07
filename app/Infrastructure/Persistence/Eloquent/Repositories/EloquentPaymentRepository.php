<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Order\Entities\OrderEntity;
use App\Domain\Payment\Contracts\PaymentRepository;
use App\Domain\Payment\Entities\PaymentEntity;
use App\Domain\Payment\Entities\PaymentMethodEntity;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Infrastructure\Persistence\Eloquent\Mappers\Payment\PaymentMapper;
use App\Infrastructure\Persistence\Eloquent\Models\Payment;
use App\Infrastructure\Persistence\Eloquent\Models\PaymentTransaction;

final class EloquentPaymentRepository implements PaymentRepository
{
    public function createPending(
        OrderEntity $order,
        PaymentMethodEntity $method,
        string $reference,
        string $idempotencyOwner,
        string $idempotencyKey,
        string $requestHash,
    ): PaymentEntity {
        $payment = Payment::create([
            'order_id' => $order->id,
            'idempotency_owner' => $idempotencyOwner,
            'idempotency_key' => $idempotencyKey,
            'idempotency_request_hash' => $requestHash,
            'payment_method_id' => $method->id,
            'provider' => $method->code,
            'reference' => $reference,
            'amount' => $order->total(),
            'currency' => $order->currency,
            'status' => PaymentStatus::PENDING->value,
            'metadata' => [],
        ]);

        $this->recordTransaction(
            paymentId: (string) $payment->id,
            type: 'create',
            status: PaymentStatus::PENDING->value,
            providerReference: null,
            amount: (float) $payment->amount,
            currency: $payment->currency,
            payload: [],
            eventKey: "payment:{$payment->id}:created",
        );

        return PaymentMapper::toDomain($payment);
    }

    public function updateInitialized(string $paymentId, ?string $authorizationUrl, string $status, array $metadata = []): PaymentEntity
    {
        $payment = Payment::query()->whereKey($paymentId)->firstOrFail();

        $payment->update([
            'authorization_url' => $authorizationUrl,
            'status' => $status,
            'metadata' => array_merge($payment->metadata ?? [], $metadata),
        ]);

        $this->recordTransaction(
            paymentId: $paymentId,
            type: 'initialize',
            status: $status,
            providerReference: $payment->provider_reference,
            amount: (float) $payment->amount,
            currency: $payment->currency,
            payload: $metadata,
            eventKey: "payment:{$paymentId}:initialized",
        );

        return PaymentMapper::toDomain($payment->refresh());
    }

    public function findByReference(string $reference): ?PaymentEntity
    {
        $payment = Payment::query()
            ->where('reference', $reference)
            ->first();

        return $payment ? PaymentMapper::toDomain($payment) : null;
    }

    public function findByReferenceForUpdate(string $reference): ?PaymentEntity
    {
        $payment = Payment::query()
            ->where('reference', $reference)
            ->lockForUpdate()
            ->first();

        return $payment ? PaymentMapper::toDomain($payment) : null;
    }

    public function findByIdempotency(string $owner, string $key): ?PaymentEntity
    {
        $payment = Payment::query()
            ->where('idempotency_owner', $owner)
            ->where('idempotency_key', $key)
            ->first();

        return $payment ? PaymentMapper::toDomain($payment) : null;
    }

    public function markAsPaid(string $paymentId, ?string $providerReference, ?string $transactionId, array $payload = []): PaymentEntity
    {
        $payment = Payment::query()->whereKey($paymentId)->firstOrFail();

        $payment->update([
            'status' => PaymentStatus::PAID->value,
            'provider_reference' => $providerReference,
            'transaction_id' => $transactionId,
            'paid_at' => now(),
            'metadata' => array_merge($payment->metadata ?? [], ['paid_payload' => $payload]),
        ]);

        $this->recordTransaction(
            paymentId: $paymentId,
            type: 'paid',
            status: PaymentStatus::PAID->value,
            providerReference: $providerReference,
            amount: (float) $payment->amount,
            currency: $payment->currency,
            payload: $payload,
            eventKey: "payment:{$paymentId}:paid:{$transactionId}",
        );

        return PaymentMapper::toDomain($payment->refresh());
    }

    public function markAsFailed(string $paymentId, ?string $providerReference, ?string $transactionId, array $payload = []): PaymentEntity
    {
        $payment = Payment::query()->whereKey($paymentId)->firstOrFail();

        $payment->update([
            'status' => PaymentStatus::FAILED->value,
            'provider_reference' => $providerReference,
            'transaction_id' => $transactionId,
            'failed_at' => now(),
            'metadata' => array_merge($payment->metadata ?? [], ['failed_payload' => $payload]),
        ]);

        $this->recordTransaction(
            paymentId: $paymentId,
            type: 'failed',
            status: PaymentStatus::FAILED->value,
            providerReference: $providerReference,
            amount: (float) $payment->amount,
            currency: $payment->currency,
            payload: $payload,
            eventKey: "payment:{$paymentId}:failed:{$transactionId}",
        );

        return PaymentMapper::toDomain($payment->refresh());
    }

    public function recordTransaction(
        string $paymentId,
        string $type,
        ?string $status,
        ?string $providerReference,
        ?float $amount,
        ?string $currency,
        array $payload = [],
        ?string $eventKey = null,
    ): bool {
        $attributes = [
            'payment_id' => $paymentId,
            'event_key' => $eventKey,
            'type' => $type,
            'status' => $status,
            'provider_reference' => $providerReference,
            'amount' => $amount,
            'currency' => $currency,
            'payload' => $payload,
        ];

        $transaction = $eventKey === null
            ? PaymentTransaction::query()->create($attributes)
            : PaymentTransaction::query()->firstOrCreate(['event_key' => $eventKey], $attributes);

        return $transaction->wasRecentlyCreated;
    }

    public function referenceExists(string $reference): bool
    {
        return Payment::query()
            ->where('reference', $reference)
            ->exists();
    }
}
