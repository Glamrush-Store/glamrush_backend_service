<?php

namespace App\Domain\Payment\Contracts;

use App\Domain\Payment\Entities\PaymentMethodEntity;

interface PaymentMethodRepository
{
    /**
     * @return PaymentMethodEntity[]
     */
    public function active(): array;

    public function findActiveByCode(string $code): ?PaymentMethodEntity;
}
