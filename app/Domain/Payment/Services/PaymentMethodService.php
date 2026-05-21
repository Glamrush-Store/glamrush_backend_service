<?php

namespace App\Domain\Payment\Services;

use App\Domain\Payment\Contracts\PaymentMethodRepository;

final class PaymentMethodService
{
    public function __construct(
        private readonly PaymentMethodRepository $methods,
    ) {
    }

    public function active(): array
    {
        return $this->methods->active();
    }
}
