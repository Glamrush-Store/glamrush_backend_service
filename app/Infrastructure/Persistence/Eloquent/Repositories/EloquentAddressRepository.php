<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\User\Contracts\AddressRepository;
use App\Domain\User\Entities\AddressEntity;
use App\Infrastructure\Persistence\Eloquent\Mappers\Address\AddressMapper;
use App\Infrastructure\Persistence\Eloquent\Models\CustomerAddress;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class EloquentAddressRepository implements AddressRepository
{
    public function listForUser(int $userId): Collection
    {
        return CustomerAddress::where('user_id', $userId)
            ->orderByDesc('is_default')
            ->orderBy('created_at')
            ->get()
            ->map(fn(CustomerAddress $m) => AddressMapper::toDomain($m));
    }

    public function findForUser(int $userId, string $id): AddressEntity
    {
        $model = CustomerAddress::where('user_id', $userId)
            ->where('id', $id)
            ->firstOrFail();

        return AddressMapper::toDomain($model);
    }

    public function create(int $userId, array $data): AddressEntity
    {
        return DB::transaction(function () use ($userId, $data) {
            $isDefault = (bool)($data['is_default'] ?? !CustomerAddress::where('user_id', $userId)->exists());

            if ($isDefault) {
                CustomerAddress::where('user_id', $userId)->update(['is_default' => false]);
            }

            $model = CustomerAddress::create(
                array_merge(
                    $data,
                    ['user_id' => $userId, 'is_default' => $isDefault],
                )
            );

            return AddressMapper::toDomain($model);
        });
    }

    public function update(int $userId, string $id, array $data): AddressEntity
    {
        return DB::transaction(function () use ($userId, $id, $data) {
            $model = CustomerAddress::where('user_id', $userId)
                ->where('id', $id)
                ->firstOrFail();

            if (!empty($data['is_default'])) {
                CustomerAddress::where('user_id', $userId)->update(['is_default' => false]);
            }

            $model->fill($data)->save();

            return AddressMapper::toDomain($model->fresh());
        });
    }

    public function delete(int $userId, string $id): void
    {
        CustomerAddress::where('user_id', $userId)
            ->where('id', $id)
            ->firstOrFail()
            ->delete();
    }

    public function setDefault(int $userId, string $id): AddressEntity
    {
        return DB::transaction(function () use ($userId, $id) {
            CustomerAddress::where('user_id', $userId)->update(['is_default' => false]);

            $model = CustomerAddress::where('user_id', $userId)
                ->where('id', $id)
                ->firstOrFail();

            $model->is_default = true;
            $model->save();

            return AddressMapper::toDomain($model);
        });
    }
}
