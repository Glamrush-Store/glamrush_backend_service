<?php

namespace App\Domain\Catalog\SavedItem\Contracts;

use App\Domain\Catalog\SavedItem\Entities\SavedItemEntity;
use Illuminate\Support\Collection;

interface SavedItemRepository
{
    public function getForUser(int $userId): Collection;

    public function findForUser(int $userId, string $productId): ?SavedItemEntity;

    public function add(int $userId, string $productId): array;

    public function remove(int $userId, string $productId): void;

    public function syncForUser(int $userId, array $productIds): void;
}
