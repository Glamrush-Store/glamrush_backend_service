<?php

namespace App\Infrastructure\Events\Laravel;

use App\Shared\Events\Contracts\DomainEvent;
use App\Shared\Events\Contracts\EventBus;
use Illuminate\Contracts\Events\Dispatcher;

final readonly class LaravelEventBus implements EventBus
{
    public function __construct(private Dispatcher $dispatcher) {}

    public function publish(DomainEvent $event, ?string $messageId = null): void
    {
        $this->dispatcher->dispatch($event);
    }
}
