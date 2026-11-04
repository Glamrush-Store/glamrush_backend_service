<?php

namespace App\Infrastructure\Payment\Gateways;

use App\Domain\Order\Entities\OrderEntity;
use App\Domain\Payment\Contracts\PaymentGateway;
use App\Domain\Payment\Entities\PaymentEntity;
use App\Domain\Payment\Entities\PaymentInitializationEntity;
use App\Domain\Payment\Entities\PaymentMethodEntity;
use App\Domain\Payment\Entities\PaymentVerificationEntity;
use App\Domain\Payment\Enums\PaymentStatus;
use RuntimeException;

final class PayOnDeliveryGateway implements PaymentGateway
{
    public function code(): string
    {
        return 'pay_on_delivery';
    }

    public function initialize(OrderEntity $order, PaymentEntity $payment, PaymentMethodEntity $method): PaymentInitializationEntity
    {
        return new PaymentInitializationEntity(
            payment: $payment,
            authorizationUrl: null,
            accessCode: null,
            reference: $payment->reference,
            provider: $this->code(),
            status: PaymentStatus::PENDING_ON_DELIVERY->value,
        );
    }

    public function verify(string $transactionId): PaymentVerificationEntity
    {
        throw new RuntimeException('Pay on delivery payments cannot be verified through a gateway.');
    }

    public function webhookIsValid(string $rawPayload, ?string $signature): bool
    {
        return false;
    }

    public function transactionIdFromWebhook(array $payload): ?string
    {
        return null;
    }
}
