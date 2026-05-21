<?php

namespace App\Domain\Payment\Services;

use App\Domain\Order\Contracts\OrderRepository;
use App\Domain\Order\Events\OrderPaid;
use App\Domain\Order\Events\OrderPendingOnDelivery;
use App\Domain\Payment\Actions\GeneratePaymentReferenceAction;
use App\Domain\Payment\Contracts\PaymentMethodRepository;
use App\Domain\Payment\Contracts\PaymentRepository;
use App\Domain\Payment\Entities\PaymentInitializationEntity;
use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Events\PaymentFailed;
use App\Infrastructure\Payment\PaymentGatewayResolver;
use RuntimeException;

final class PaymentService
{
    public function __construct(
        private readonly OrderRepository $orders,
        private readonly PaymentMethodRepository $methods,
        private readonly PaymentRepository $payments,
        private readonly PaymentGatewayResolver $gatewayResolver,
        private readonly GeneratePaymentReferenceAction $generatePaymentReference,
    ) {
    }

    public function initialize(string $orderId, string $paymentMethodCode): PaymentInitializationEntity
    {
        $order = $this->orders->findById($orderId);

        if ($order === null) {
            throw new RuntimeException('Order not found.');
        }

        if (! $order->isPendingPayment()) {
            throw new RuntimeException('Only pending payment orders can be paid.');
        }

        $method = $this->methods->findActiveByCode($paymentMethodCode);

        if ($method === null) {
            throw new RuntimeException('Payment method is not available.');
        }

        $gateway = $this->gatewayResolver->resolve($method->code);
        $payment = $this->payments->createPending(
            order: $order,
            method: $method,
            reference: $this->generatePaymentReference->run(),
        );

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
            $this->orders->markAsPendingOnDelivery($order->id);
            event(new OrderPendingOnDelivery($order->id, $payment->id));
        }

        return new PaymentInitializationEntity(
            payment: $payment,
            authorizationUrl: $initialization->authorizationUrl,
            accessCode: $initialization->accessCode,
            reference: $initialization->reference,
            provider: $initialization->provider,
            status: $initialization->status,
        );
    }

    public function verify(string $provider, string $transactionId): PaymentInitializationEntity
    {
        $gateway = $this->gatewayResolver->resolve($provider);
        $verification = $gateway->verify($transactionId);

        if ($verification->reference === '') {
            throw new RuntimeException('Payment reference not found in gateway verification response.');
        }

        $payment = $this->payments->findByReference($verification->reference);

        if ($payment === null) {
            throw new RuntimeException('Payment not found.');
        }

        if ($payment->provider !== $provider) {
            throw new RuntimeException('Payment provider mismatch.');
        }

        $this->payments->recordTransaction(
            paymentId: $payment->id,
            type: 'verify',
            status: $verification->status,
            providerReference: $verification->providerReference,
            amount: $verification->amount,
            currency: $verification->currency,
            payload: $verification->payload,
        );

        if ($verification->succeeded() && ! $payment->isPaid()) {
            if ($verification->amount !== null && round($verification->amount, 2) !== round($payment->amount, 2)) {
                throw new RuntimeException('Payment amount mismatch.');
            }

            if ($verification->currency !== null && $verification->currency !== $payment->currency) {
                throw new RuntimeException('Payment currency mismatch.');
            }

            $payment = $this->payments->markAsPaid(
                paymentId: $payment->id,
                providerReference: $verification->providerReference,
                transactionId: $verification->transactionId,
                payload: $verification->payload,
            );

            $this->orders->markAsPaid($payment->orderId);
            event(new OrderPaid($payment->orderId));
        }

        if (
            ! $verification->succeeded() &&
            in_array($payment->status, [PaymentStatus::PENDING->value, PaymentStatus::INITIALIZED->value], true)
        ) {
            $payment = $this->payments->markAsFailed(
                paymentId: $payment->id,
                providerReference: $verification->providerReference,
                transactionId: $verification->transactionId,
                payload: $verification->payload,
            );
            event(new PaymentFailed($payment->id));
        }

        return new PaymentInitializationEntity(
            payment: $payment,
            authorizationUrl: $payment->authorizationUrl,
            accessCode: $payment->metadata['access_code'] ?? null,
            reference: $payment->reference,
            provider: $payment->provider,
            status: $payment->status,
        );
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
}
