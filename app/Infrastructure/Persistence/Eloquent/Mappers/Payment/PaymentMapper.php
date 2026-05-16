<?php

namespace App\Infrastructure\Persistence\Eloquent\Mappers\Payment;

use App\Domain\Payment\Entities\PaymentEntity;
use App\Infrastructure\Persistence\Eloquent\Models\Payment;
use DateTimeImmutable;

final class PaymentMapper
{
    public static function toDomain(Payment $model): PaymentEntity
    {
        return new PaymentEntity(
            id: (string) $model->id,
            orderId: (string) $model->order_id,
            paymentMethodId: $model->payment_method_id !== null ? (string) $model->payment_method_id : null,
            provider: $model->provider,
            reference: $model->reference,
            providerReference: $model->provider_reference,
            transactionId: $model->transaction_id,
            amount: (float) $model->amount,
            currency: $model->currency,
            status: $model->status,
            authorizationUrl: $model->authorization_url,
            paidAt: $model->paid_at !== null ? DateTimeImmutable::createFromInterface($model->paid_at) : null,
            failedAt: $model->failed_at !== null ? DateTimeImmutable::createFromInterface($model->failed_at) : null,
            metadata: $model->metadata ?? [],
        );
    }
}
