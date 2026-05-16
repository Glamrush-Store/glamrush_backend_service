<?php
/*
 * © 2026 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Domain\Order\Contracts;


use App\Domain\Order\Entities\CreateOrderEntity;
use App\Domain\Order\Entities\OrderEntity;
use Illuminate\Pagination\LengthAwarePaginator;

interface OrderRepository
{

    public function createPendingOrder(CreateOrderEntity $data): OrderEntity;

    public function findById(string $id): ?OrderEntity;

    public function findByOrderNumber(string $orderNumber): ?OrderEntity;

    public function markAsPaid(string $orderId): void;

    public function markAsProcessing(string $orderId): void;

    public function markAsPendingOnDelivery(string $orderId): void;

    public function cancelPendingOrder(string $orderId): void;

    public function paginateByUserId(string $userId, int $perPage = 15, int $page = 1): LengthAwarePaginator;

    public function orderNumberExists(string $orderNumber): bool;


}
