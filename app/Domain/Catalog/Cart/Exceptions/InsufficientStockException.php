<?php

namespace App\Domain\Catalog\Cart\Exceptions;

use RuntimeException;

final class InsufficientStockException extends RuntimeException
{
    public function __construct(string $productName = null, int $available)
    {
        $name = $productName ? "for {$productName}" : '';
        parent::__construct("Only {$available} unit(s) available in stock {$name}.");
    }
}
