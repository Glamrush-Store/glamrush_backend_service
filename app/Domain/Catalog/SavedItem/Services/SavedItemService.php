<?php

namespace App\Domain\Catalog\SavedItem\Services;

use App\Domain\Catalog\SavedItem\Contracts\SavedItemRepository;
use Illuminate\Support\Collection;

final class SavedItemService
{
    public function __construct(
        private readonly SavedItemRepository $repository,
    ) {}

    public function list(int $userId): Collection
    {
        return $this->repository->getForUser($userId);
    }

    public function add(int $userId, string $productId): array
    {
        return $this->repository->add($userId, $productId);
    }

    public function remove(int $userId, string $productId): void
    {
        $this->repository->remove($userId, $productId);
    }

    public function sync(int $userId, array $productIds): Collection
    {
        $this->repository->syncForUser($userId, $productIds);

        return $this->repository->getForUser($userId);
    }
}
