<?php

namespace App\Domain\Payment\Contracts;

use App\Domain\Order\Entities\OrderEntity;
use App\Domain\Payment\Entities\PaymentEntity;
use App\Domain\Payment\Entities\PaymentInitializationEntity;
use App\Domain\Payment\Entities\PaymentMethodEntity;
use App\Domain\Payment\Entities\PaymentVerificationEntity;

interface PaymentGateway
{
    public function code(): string;

    public function initialize(OrderEntity $order, PaymentEntity $payment, PaymentMethodEntity $method): PaymentInitializationEntity;

    public function verify(string $transactionId): PaymentVerificationEntity;

    public function webhookIsValid(string $rawPayload, ?string $signature): bool;

    public function transactionIdFromWebhook(array $payload): ?string;
}
