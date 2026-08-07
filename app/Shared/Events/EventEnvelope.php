<?php

namespace App\Shared\Events;

use App\Shared\Events\Contracts\DomainEvent;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use InvalidArgumentException;
use JsonException;

final readonly class EventEnvelope
{
    /** @param array<string, mixed> $payload */
    public function __construct(
        public string $id,
        public string $type,
        public array $payload,
        public string $occurredAt,
        public int $schemaVersion = 1,
    ) {
        if ($this->id === '' || $this->type === '') {
            throw new InvalidArgumentException('Event envelope id and type are required.');
        }
    }

    public static function fromEvent(DomainEvent $event, ?string $messageId = null): self
    {
        return new self(
            $messageId ?? (string) Str::uuid(),
            $event->eventType(),
            $event->eventPayload(),
            CarbonImmutable::now()->toIso8601String(),
        );
    }

    /** @return array{id: string, type: string, payload: array<string, mixed>, occurred_at: string, schema_version: int} */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'payload' => $this->payload,
            'occurred_at' => $this->occurredAt,
            'schema_version' => $this->schemaVersion,
        ];
    }

    /** @throws JsonException */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }

    /** @throws JsonException */
    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

        if (! is_array($data) || ! is_array($data['payload'] ?? null)) {
            throw new InvalidArgumentException('Invalid event envelope.');
        }

        return new self(
            (string) ($data['id'] ?? ''),
            (string) ($data['type'] ?? ''),
            $data['payload'],
            (string) ($data['occurred_at'] ?? ''),
            (int) ($data['schema_version'] ?? 1),
        );
    }
}
