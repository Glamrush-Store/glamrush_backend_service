<?php

namespace App\Infrastructure\Payment;

use App\Domain\Payment\Contracts\PaymentGateway;
use App\Infrastructure\Payment\Gateways\FlutterwavePaymentGateway;
use App\Infrastructure\Payment\Gateways\PayOnDeliveryGateway;
use App\Infrastructure\Payment\Gateways\PaystackPaymentGateway;
use InvalidArgumentException;

final class PaymentGatewayResolver
{
    public function __construct(
        private readonly PaystackPaymentGateway $paystack,
        private readonly FlutterwavePaymentGateway $flutterwave,
        private readonly PayOnDeliveryGateway $payOnDelivery,
    ) {
    }

    public function resolve(string $code): PaymentGateway
    {
        return match ($code) {
            'paystack' => $this->paystack,
            'flutterwave' => $this->flutterwave,
            'pay_on_delivery' => $this->payOnDelivery,
            default => throw new InvalidArgumentException("Unsupported payment provider [{$code}]."),
        };
    }
}
