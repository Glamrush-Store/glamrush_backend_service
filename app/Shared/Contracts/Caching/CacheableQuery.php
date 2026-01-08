<?php
/*
 * © 2025 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Shared\Contracts\Caching;

interface CacheableQuery
{
    public function cacheKey(): string;

    public function cacheTags(): array;

    public function ttl(): int;
}
