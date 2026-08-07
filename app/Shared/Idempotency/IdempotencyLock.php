<?php

namespace App\Shared\Idempotency;

use Closure;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

final class IdempotencyLock
{
    public function run(string $scope, string $owner, string $key, Closure $callback): mixed
    {
        $lockName = 'idempotency:'.hash('sha256', "{$scope}:{$owner}:{$key}");

        try {
            return Cache::lock(
                $lockName,
                (int) config('idempotency.lock_seconds', 60),
            )->block(
                (int) config('idempotency.wait_seconds', 10),
                $callback,
            );
        } catch (LockTimeoutException) {
            throw new RuntimeException('An identical request is still being processed. Please retry shortly.');
        }
    }
}
