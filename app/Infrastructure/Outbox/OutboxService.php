<?php

namespace App\Infrastructure\Outbox;

use App\Infrastructure\Persistence\Eloquent\Models\OutboxMessage;
use App\Jobs\DeliverOutboxMessage;
use App\Shared\Events\Contracts\DomainEvent;

final class OutboxService
{
    public function recordEvent(string $eventKey, DomainEvent $event): string
    {
        return $this->record($eventKey, $event->eventType(), $event->eventPayload());
    }

    public function record(string $eventKey, string $type, array $payload): string
    {
        $message = OutboxMessage::query()->firstOrCreate(
            ['event_key' => $eventKey],
            [
                'type' => $type,
                'payload' => $payload,
                'available_at' => now(),
            ],
        );

        DeliverOutboxMessage::dispatch((string) $message->id)->afterCommit();

        return (string) $message->id;
    }
}
