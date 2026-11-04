<?php

namespace App\Shared\Events\Contracts;

use App\Shared\Events\EventEnvelope;

interface EventConsumer
{
    /** @param callable(EventEnvelope): void $handler */
    public function consume(callable $handler, int $maxMessages = 0): void;
}
