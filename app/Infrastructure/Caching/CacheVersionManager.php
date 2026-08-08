<?php

namespace App\Infrastructure\Caching;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class CacheVersionManager
{
    private const SNAPSHOT_TAG = 'cache-version-snapshots';

    /** @param list<string> $tags */
    public function versionedKey(string $key, array $tags): string
    {
        $namespaces = $this->namespacesForTags($tags);

        if ($namespaces === []) {
            return $key;
        }

        $versions = $this->versions($namespaces);
        $suffix = collect($versions)
            ->map(fn (int $version, string $namespace): string => "{$namespace}={$version}")
            ->implode('|');

        return "{$key}:cv:".hash('sha256', $suffix);
    }

    /** @param list<string> $tags */
    public function bumpForTags(array $tags): void
    {
        $this->bump($this->namespacesForTags($tags));
    }

    /** @param list<string> $namespaces */
    public function bump(array $namespaces): void
    {
        $namespaces = $this->normalizeNamespaces($namespaces);

        if ($namespaces === []) {
            return;
        }

        try {
            DB::transaction(function () use ($namespaces): void {
                $now = now();

                foreach ($namespaces as $namespace) {
                    DB::table('cache_versions')->insertOrIgnore([
                        'namespace' => $namespace,
                        'version' => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    DB::table('cache_versions')
                        ->where('namespace', $namespace)
                        ->increment('version', 1, ['updated_at' => $now]);
                }
            });

            Cache::tags([self::SNAPSHOT_TAG])->flush();
        } catch (Throwable $exception) {
            Log::warning('Unable to bump shared cache versions.', [
                'namespaces' => $namespaces,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    /** @param list<string> $tags
     * @return list<string>
     */
    public function namespacesForTags(array $tags): array
    {
        $mapping = (array) config('cache_versions.tag_namespaces', []);

        return $this->normalizeNamespaces(array_values(array_filter(array_map(
            fn (string $tag): mixed => $mapping[$tag] ?? null,
            $tags,
        ))));
    }

    /** @param list<string> $namespaces
     * @return array<string, int>
     */
    private function versions(array $namespaces): array
    {
        $cacheKey = 'cache-version-snapshot:'.hash('sha256', implode('|', $namespaces));
        $ttl = max(0, (int) config('cache_versions.check_ttl_seconds', 10));

        try {
            if ($ttl === 0) {
                return $this->readVersions($namespaces);
            }

            return Cache::tags([self::SNAPSHOT_TAG])->remember(
                $cacheKey,
                now()->addSeconds($ttl),
                fn (): array => $this->readVersions($namespaces),
            );
        } catch (Throwable $exception) {
            Log::warning('Unable to read shared cache versions; using the initial version.', [
                'namespaces' => $namespaces,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return array_fill_keys($namespaces, 1);
        }
    }

    /** @param list<string> $namespaces
     * @return array<string, int>
     */
    private function readVersions(array $namespaces): array
    {
        $stored = DB::table('cache_versions')
            ->whereIn('namespace', $namespaces)
            ->pluck('version', 'namespace');

        $versions = [];

        foreach ($namespaces as $namespace) {
            $versions[$namespace] = max(1, (int) ($stored[$namespace] ?? 1));
        }

        return $versions;
    }

    /** @param array<int, mixed> $namespaces
     * @return list<string>
     */
    private function normalizeNamespaces(array $namespaces): array
    {
        $normalized = array_filter(
            $namespaces,
            fn (mixed $namespace): bool => is_string($namespace)
                && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $namespace) === 1,
        );

        sort($normalized);

        return array_values(array_unique($normalized));
    }
}
