<?php

namespace App\Domain\Payment\Enums;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case INITIALIZED = 'initialized';
    case PENDING_ON_DELIVERY = 'pending_on_delivery';
    case PAID = 'paid';
    case FAILED = 'failed';
}
