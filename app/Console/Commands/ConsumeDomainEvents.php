<?php

namespace App\Console\Commands;

use App\Shared\Events\Contracts\EventConsumer;
use App\Shared\Events\LocalEventDispatcher;
use Illuminate\Console\Command;

final class ConsumeDomainEvents extends Command
{
    protected $signature = 'events:consume {--max=0 : Stop after this many events; zero runs continuously}';

    protected $description = 'Consume domain events from the configured external event bus';

    public function handle(EventConsumer $consumer, LocalEventDispatcher $dispatcher): int
    {
        if (config('event_bus.default') === 'laravel') {
            $this->components->info('Laravel events are dispatched in-process; no event consumer is required.');

            return self::SUCCESS;
        }

        $maxMessages = max(0, (int) $this->option('max'));
        $this->components->info('Consuming events from the '.config('event_bus.default').' driver.');
        $consumer->consume(
            fn ($envelope) => $dispatcher->dispatch($envelope),
            $maxMessages,
        );

        return self::SUCCESS;
    }
}
