<?php

namespace App\Domain\Catalog\Cart\Exceptions;

use RuntimeException;

final class InsufficientStockException extends RuntimeException
{
    public function __construct(int $available)
    {
        parent::__construct("Only {$available} unit(s) available in stock.");
    }
}
