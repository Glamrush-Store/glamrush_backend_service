<?php

namespace App\Domain\User\Contracts;

use App\Domain\User\Entities\AddressEntity;
use Illuminate\Support\Collection;

interface AddressRepository
{
    public function listForUser(int $userId): Collection;

    public function findForUser(int $userId, string $id): AddressEntity;

    public function create(int $userId, array $data): AddressEntity;

    public function update(int $userId, string $id, array $data): AddressEntity;

    public function delete(int $userId, string $id): void;

    public function setDefault(int $userId, string $id): AddressEntity;
}
