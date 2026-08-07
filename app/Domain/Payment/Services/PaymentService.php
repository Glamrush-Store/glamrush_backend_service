<?php

namespace App\Domain\Payment\Services;

use App\Domain\Order\Contracts\OrderRepository;
use App\Domain\Payment\Actions\GeneratePaymentReferenceAction;
use App\Domain\Payment\Contracts\PaymentMethodRepository;
use App\Domain\Payment\Contracts\PaymentRepository;
use App\Domain\Payment\Entities\PaymentEntity;
use App\Domain\Payment\Entities\PaymentInitializationEntity;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Infrastructure\Outbox\OutboxService;
use App\Infrastructure\Payment\PaymentGatewayResolver;
use App\Shared\Idempotency\IdempotencyLock;
use App\Shared\Idempotency\IdempotencyOwner;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class PaymentService
{
    public function __construct(
        private readonly OrderRepository $orders,
        private readonly PaymentMethodRepository $methods,
        private readonly PaymentRepository $payments,
        private readonly PaymentGatewayResolver $gatewayResolver,
        private readonly GeneratePaymentReferenceAction $generatePaymentReference,
        private readonly IdempotencyLock $idempotencyLock,
        private readonly OutboxService $outbox,
    ) {}

    public function initialize(
        string $orderId,
        string $paymentMethodCode,
        ?int $userId,
        ?string $guestToken,
        string $idempotencyKey,
        string $requestHash,
    ): PaymentInitializationEntity {
        $owner = IdempotencyOwner::from($userId, $guestToken);
        $order = $this->orders->findByIdForOwner($orderId, $userId, $guestToken);

        if ($order === null) {
            throw new RuntimeException('Order not found or does not belong to this customer.');
        }

        return $this->idempotencyLock->run(
            "payment-initialize:{$orderId}",
            $owner,
            'order-initialization',
            function () use ($orderId, $paymentMethodCode, $userId, $guestToken, $owner, $idempotencyKey, $requestHash) {
                $order = $this->orders->findByIdForOwner($orderId, $userId, $guestToken);

                if ($order === null) {
                    throw new RuntimeException('Order not found or does not belong to this customer.');
                }

                $payment = $this->payments->findByIdempotency($owner, $idempotencyKey);
                $replayed = $payment !== null;

                if ($payment !== null) {
                    if (! hash_equals((string) $payment->idempotencyRequestHash, $requestHash)) {
                        throw new RuntimeException('This Idempotency-Key was already used with a different payment request.');
                    }

                    if ($payment->authorizationUrl !== null || $payment->status !== PaymentStatus::PENDING->value) {
                        return $this->initializationResponse($payment, true);
                    }
                }

                if (! $order->isPendingPayment()) {
                    throw new RuntimeException('Only pending payment orders can be paid.');
                }

                $method = $this->methods->findActiveByCode($paymentMethodCode);

                if ($method === null) {
                    throw new RuntimeException('Payment method is not available.');
                }

                $gateway = $this->gatewayResolver->resolve($method->code);

                if ($payment === null) {
                    try {
                        $payment = $this->payments->createPending(
                            order: $order,
                            method: $method,
                            reference: $this->generatePaymentReference->run(),
                            idempotencyOwner: $owner,
                            idempotencyKey: $idempotencyKey,
                            requestHash: $requestHash,
                        );
                    } catch (UniqueConstraintViolationException) {
                        $payment = $this->payments->findByIdempotency($owner, $idempotencyKey);

                        if ($payment === null || ! hash_equals((string) $payment->idempotencyRequestHash, $requestHash)) {
                            throw new RuntimeException('Unable to safely resume this payment request.');
                        }

                        $replayed = true;
                    }
                }

                $initialization = $gateway->initialize($order, $payment, $method);

                $payment = $this->payments->updateInitialized(
                    paymentId: $payment->id,
                    authorizationUrl: $initialization->authorizationUrl,
                    status: $initialization->status,
                    metadata: [
                        'access_code' => $initialization->accessCode,
                        'initialization_status' => $initialization->status,
                    ],
                );

                if ($initialization->status === PaymentStatus::PENDING_ON_DELIVERY->value) {
                    DB::transaction(function () use ($order, $payment): void {
                        if ($this->orders->markAsPendingOnDelivery($order->id)) {
                            $this->outbox->record(
                                "order:{$order->id}:pending-on-delivery",
                                'order.pending_on_delivery',
                                ['order_id' => $order->id, 'payment_id' => $payment->id],
                            );
                        }
                    });
                }

                return new PaymentInitializationEntity(
                    payment: $payment,
                    authorizationUrl: $initialization->authorizationUrl,
                    accessCode: $initialization->accessCode,
                    reference: $initialization->reference,
                    provider: $initialization->provider,
                    status: $initialization->status,
                    replayed: $replayed,
                );
            },
        );
    }

    public function verify(string $provider, string $transactionId): PaymentInitializationEntity
    {
        $gateway = $this->gatewayResolver->resolve($provider);
        $verification = $gateway->verify($transactionId);

        if ($verification->reference === '') {
            throw new RuntimeException('Payment reference not found in gateway verification response.');
        }

        return DB::transaction(function () use ($provider, $transactionId, $verification): PaymentInitializationEntity {
            $payment = $this->payments->findByReferenceForUpdate($verification->reference);

            if ($payment === null) {
                throw new RuntimeException('Payment not found.');
            }

            if ($payment->provider !== $provider) {
                throw new RuntimeException('Payment provider mismatch.');
            }

            $eventBase = "provider:{$provider}:transaction:{$transactionId}";
            $this->payments->recordTransaction(
                paymentId: $payment->id,
                type: 'verify',
                status: $verification->status,
                providerReference: $verification->providerReference,
                amount: $verification->amount,
                currency: $verification->currency,
                payload: $verification->payload,
                eventKey: "{$eventBase}:verify",
            );

            if ($verification->succeeded()) {
                $this->assertVerifiedTotals($payment, $verification->amount, $verification->currency);

                if (! $payment->isPaid()) {
                    try {
                        $payment = $this->payments->markAsPaid(
                            paymentId: $payment->id,
                            providerReference: $verification->providerReference,
                            transactionId: $verification->transactionId,
                            payload: $verification->payload,
                        );
                    } catch (UniqueConstraintViolationException) {
                        throw new RuntimeException('This provider transaction has already been applied to another payment.');
                    }
                }

                if ($this->orders->markPaidAndCommitInventory($payment->orderId)) {
                    $this->outbox->record(
                        "order:{$payment->orderId}:paid",
                        'order.paid',
                        ['order_id' => $payment->orderId],
                    );
                }
            } elseif (in_array($payment->status, [PaymentStatus::PENDING->value, PaymentStatus::INITIALIZED->value], true)) {
                $payment = $this->payments->markAsFailed(
                    paymentId: $payment->id,
                    providerReference: $verification->providerReference,
                    transactionId: $verification->transactionId,
                    payload: $verification->payload,
                );

                $this->outbox->record(
                    "payment:{$payment->id}:failed",
                    'payment.failed',
                    ['payment_id' => $payment->id],
                );
            }

            return $this->initializationResponse($payment, false);
        });
    }

    public function handleWebhook(string $provider, string $rawPayload, ?string $signature): void
    {
        $gateway = $this->gatewayResolver->resolve($provider);

        if (! $gateway->webhookIsValid($rawPayload, $signature)) {
            throw new RuntimeException('Invalid payment webhook signature.');
        }

        $payload = json_decode($rawPayload, true);
        $transactionId = is_array($payload) ? $gateway->transactionIdFromWebhook($payload) : null;

        if ($transactionId === null) {
            throw new RuntimeException('Payment transaction id not found in webhook payload.');
        }

        $this->verify($provider, $transactionId);
    }

    private function assertVerifiedTotals(PaymentEntity $payment, ?float $amount, ?string $currency): void
    {
        if ($amount !== null && round($amount, 2) !== round($payment->amount, 2)) {
            throw new RuntimeException('Payment amount mismatch.');
        }

        if ($currency !== null && $currency !== $payment->currency) {
            throw new RuntimeException('Payment currency mismatch.');
        }
    }

    private function initializationResponse(PaymentEntity $payment, bool $replayed): PaymentInitializationEntity
    {
        return new PaymentInitializationEntity(
            payment: $payment,
            authorizationUrl: $payment->authorizationUrl,
            accessCode: $payment->metadata['access_code'] ?? null,
            reference: $payment->reference,
            provider: $payment->provider,
            status: $payment->status,
            replayed: $replayed,
        );
    }
}
