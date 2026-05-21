<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Catalog\SavedItem\Contracts\SavedItemRepository;
use App\Domain\Catalog\SavedItem\Entities\SavedItemEntity;
use App\Infrastructure\Persistence\Eloquent\Mappers\Catalog\SavedItemMapper;
use App\Infrastructure\Persistence\Eloquent\Models\SavedItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class EloquentSavedItemRepository implements SavedItemRepository
{
    public function getForUser(int $userId): Collection
    {
        return SavedItem::with(['product', 'product.media'])
            ->where('user_id', $userId)
            ->get()
            ->map(fn(SavedItem $item) => SavedItemMapper::toDomain($item));
    }

    public function findForUser(int $userId, string $productId): ?SavedItemEntity
    {
        $item = SavedItem::with(['product', 'product.media'])
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->first();

        return $item ? SavedItemMapper::toDomain($item) : null;
    }

    public function add(int $userId, string $productId): array
    {
        $item = SavedItem::firstOrCreate(
            ['user_id' => $userId, 'product_id' => $productId],
        );

        $created = $item->wasRecentlyCreated;

        $item->load(['product', 'product.media']);

        return [
            'entity' => SavedItemMapper::toDomain($item),
            'created' => $created,
        ];
    }

    public function remove(int $userId, string $productId): void
    {
        SavedItem::where('user_id', $userId)
            ->where('product_id', $productId)
            ->firstOrFail()
            ->delete();
    }

    public function syncForUser(int $userId, array $productIds): void
    {
        if (empty($productIds)) {
            return;
        }

        $existing = SavedItem::where('user_id', $userId)
            ->whereIn('product_id', $productIds)
            ->pluck('product_id')
            ->all();

        $toInsert = array_values(array_diff($productIds, $existing));

        if (empty($toInsert)) {
            return;
        }

        $now = now();
        $rows = array_map(fn(string $productId) => [
            'user_id'    => $userId,
            'product_id' => $productId,
            'created_at' => $now,
            'updated_at' => $now,
        ], $toInsert);

        DB::table('saved_items')->insert($rows);
    }
}
