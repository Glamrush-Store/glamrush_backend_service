<?php

namespace App\Infrastructure\Events\Laravel;

use App\Shared\Events\Contracts\EventConsumer;

final class LaravelEventConsumer implements EventConsumer
{
    public function consume(callable $handler, int $maxMessages = 0): void
    {
        // Laravel events are delivered in-process and require no broker worker.
    }
}
