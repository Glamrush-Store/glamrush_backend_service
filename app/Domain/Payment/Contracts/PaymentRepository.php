<?php

namespace App\Domain\Payment\Contracts;

use App\Domain\Order\Entities\OrderEntity;
use App\Domain\Payment\Entities\PaymentEntity;
use App\Domain\Payment\Entities\PaymentMethodEntity;

interface PaymentRepository
{
    public function createPending(OrderEntity $order, PaymentMethodEntity $method, string $reference): PaymentEntity;

    public function updateInitialized(string $paymentId, ?string $authorizationUrl, string $status, array $metadata = []): PaymentEntity;

    public function findByReference(string $reference): ?PaymentEntity;

    public function markAsPaid(string $paymentId, ?string $providerReference, ?string $transactionId, array $payload = []): PaymentEntity;

    public function markAsFailed(string $paymentId, ?string $providerReference, ?string $transactionId, array $payload = []): PaymentEntity;

    public function recordTransaction(
        string $paymentId,
        string $type,
        ?string $status,
        ?string $providerReference,
        ?float $amount,
        ?string $currency,
        array $payload = [],
    ): void;

    public function referenceExists(string $reference): bool;
}
