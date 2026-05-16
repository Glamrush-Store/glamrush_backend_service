<?php
/*
 * © 2026 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Domain\Order\Services;

use App\Domain\Order\Contracts\OrderRepository;
use Illuminate\Pagination\LengthAwarePaginator;

final class OrderService
{
    public function __construct(
        private readonly OrderRepository $orders,
    ) {
    }

    public function paginateForUser(string $userId, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        return $this->orders->paginateByUserId(
            userId: $userId,
            perPage: $perPage,
            page: $page,
        );
    }
}
