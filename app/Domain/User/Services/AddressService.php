<?php

namespace App\Domain\User\Services;

use App\Domain\User\Contracts\AddressRepository;
use App\Domain\User\Entities\AddressEntity;
use Illuminate\Support\Collection;

final class AddressService
{
    public function __construct(
        private readonly AddressRepository $repository,
    ) {}

    public function list(int $userId): Collection
    {
        return $this->repository->listForUser($userId);
    }

    public function find(int $userId, string $id): AddressEntity
    {
        return $this->repository->findForUser($userId, $id);
    }

    public function create(int $userId, array $data): AddressEntity
    {
        return $this->repository->create($userId, $data);
    }

    public function update(int $userId, string $id, array $data): AddressEntity
    {
        return $this->repository->update($userId, $id, $data);
    }

    public function delete(int $userId, string $id): void
    {
        $this->repository->delete($userId, $id);
    }

    public function setDefault(int $userId, string $id): AddressEntity
    {
        return $this->repository->setDefault($userId, $id);
    }
}
