<?php
/*
 * © 2026 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Domain\Order\Actions;

use App\Domain\Order\Contracts\OrderRepository;

final class GenerateOrderNumberAction
{
    public function __construct(
        private readonly OrderRepository $orders,
    ) {
    }

    public function run(): string
    {
        do {
            $number = 'GR-' . now()->format('Ymd') . '-' . str_pad(
                    (string)random_int(1, 999999),
                    6,
                    '0',
                    STR_PAD_LEFT
                );
        } while ($this->orders->orderNumberExists($number));

        return $number;
    }
}
