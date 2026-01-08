<?php
/*
 * © 2025 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Infrastructure\Caching;

use App\Shared\Contracts\Caching\CacheableQuery;
use Closure;
use Exception;
use Illuminate\Support\Facades\Cache;

final class QueryCache
{
    public static function remember(
        CacheableQuery $query,
        Closure $callback
    ): mixed {
        try {
            return Cache::tags($query->cacheTags())
                ->remember(
                    $query->cacheKey(),
                    now()->addSeconds($query->ttl()),
                    $callback
                );
        } catch (Exception $e) {
            return $callback();
        }
    }

    public static function forget(array|string $tags): void
    {
        Cache::tags((array)$tags)->flush();
    }
}
