<?php

namespace App\Infrastructure\CacheMetrics;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Redis;
use Throwable;

final class CacheMetricsRecorder
{
    public function __construct(private readonly CacheAreaResolver $areas) {}

    public function record(string $metric, mixed $key): void
    {
        if (! config('cache_metrics.enabled', true)) {
            return;
        }

        if (! in_array($metric, ['hits', 'misses', 'writes', 'forgets'], true)) {
            return;
        }

        $cacheKey = $this->normalizeKey($key);
        if ($cacheKey === null) {
            return;
        }

        $area = $this->areas->resolve($cacheKey);
        if ($area === null) {
            return;
        }

        $redisKey = $this->redisKey($area, $this->bucket());
        $ttl = (int) config('cache_metrics.raw_ttl_seconds', 172800);

        try {
            $redis = Redis::connection(config('cache_metrics.redis_connection'));
            $redis->command('hincrby', [$redisKey, $metric, 1]);
            $redis->command('hset', [$redisKey, 'service', $this->serviceName(), 'area', $area]);
            $redis->command('expire', [$redisKey, $ttl]);
        } catch (Throwable) {
            // Cache metrics must never break application cache reads/writes.
        }
    }

    private function normalizeKey(mixed $key): ?string
    {
        if (is_string($key) && trim($key) !== '') {
            return $key;
        }

        if (is_scalar($key)) {
            return (string) $key;
        }

        return null;
    }

    private function bucket(): string
    {
        $minutes = max(1, (int) config('cache_metrics.bucket_minutes', 5));
        $now = Carbon::now();
        $minute = intdiv((int) $now->format('i'), $minutes) * $minutes;

        return $now->setMinute($minute)->setSecond(0)->format('YmdHi');
    }

    private function redisKey(string $area, string $bucket): string
    {
        return implode(':', [
            trim((string) config('cache_metrics.key_prefix', 'glamrush:metrics:cache'), ':'),
            $this->serviceName(),
            $area,
            $bucket,
        ]);
    }

    private function serviceName(): string
    {
        return preg_replace('/[^a-zA-Z0-9_.-]/', '_', (string) config('cache_metrics.service_name', 'backend_service')) ?: 'backend_service';
    }
}
