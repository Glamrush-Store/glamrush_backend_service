<?php

namespace App\Shared\Events;

use App\Infrastructure\Persistence\Eloquent\Models\ConsumedEvent;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;

final readonly class LocalEventDispatcher
{
    public function __construct(
        private DomainEventRegistry $events,
        private Dispatcher $dispatcher,
    ) {}

    public function dispatch(EventEnvelope $envelope): bool
    {
        return DB::transaction(function () use ($envelope): bool {
            $now = now();
            $claimed = ConsumedEvent::query()->insertOrIgnore([
                'id' => $envelope->id,
                'type' => $envelope->type,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            if ($claimed === 0) {
                return false;
            }

            $this->dispatcher->dispatch($this->events->eventFromEnvelope($envelope));
            ConsumedEvent::query()->whereKey($envelope->id)->update([
                'processed_at' => now(),
                'updated_at' => now(),
            ]);

            return true;
        });
    }
}
