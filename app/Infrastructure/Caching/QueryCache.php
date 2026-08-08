<?php

/*
 * © 2025 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Infrastructure\Caching;

use App\Shared\Contracts\Caching\CacheableQuery;
use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

final class QueryCache
{
    public static function remember(
        CacheableQuery $query,
        Closure $callback
    ): mixed {
        return self::rememberTagged(
            $query->cacheKey(),
            $query->cacheTags(),
            $query->ttl(),
            $callback,
        );
    }

    public static function rememberTagged(
        string $key,
        array $tags,
        int $ttl,
        Closure $callback,
    ): mixed {
        if ($ttl <= 0) {
            return $callback();
        }

        $key = app(CacheVersionManager::class)->versionedKey($key, $tags);
        $missing = new \stdClass;

        try {
            $cache = Cache::tags($tags);
            $cached = $cache->get($key, $missing);
        } catch (Throwable $exception) {
            self::reportFailure('read', $key, $tags, $exception);

            return $callback();
        }

        if ($cached !== $missing) {
            return $cached;
        }

        // Deliberately execute application code outside the cache try/catch so
        // domain and database exceptions are never swallowed or retried.
        $value = $callback();

        try {
            $cache->put($key, $value, now()->addSeconds($ttl));
        } catch (Throwable $exception) {
            self::reportFailure('write', $key, $tags, $exception);
        }

        return $value;
    }

    public static function forget(array|string $tags): void
    {
        $tags = (array) $tags;

        try {
            Cache::tags($tags)->flush();
        } catch (Throwable $exception) {
            self::reportFailure('flush', null, $tags, $exception);
        }
    }

    private static function reportFailure(
        string $operation,
        ?string $key,
        array $tags,
        Throwable $exception,
    ): void {
        Log::warning('Cache operation failed; continuing without cached data.', [
            'operation' => $operation,
            'key' => $key,
            'tags' => $tags,
            'exception' => $exception::class,
            'message' => $exception->getMessage(),
        ]);
    }
}
