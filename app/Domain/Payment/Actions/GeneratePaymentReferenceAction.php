<?php

namespace App\Domain\Payment\Actions;

use App\Domain\Payment\Contracts\PaymentRepository;
use Illuminate\Support\Str;

final class GeneratePaymentReferenceAction
{
    public function __construct(
        private readonly PaymentRepository $payments,
    ) {
    }

    public function run(): string
    {
        do {
            $reference = 'PAY-' . now()->format('Ymd') . '-' . Str::upper(Str::random(10));
        } while ($this->payments->referenceExists($reference));

        return $reference;
    }
}
