<?php

namespace App\Shared\Events\Contracts;

interface DomainEvent
{
    public function eventType(): string;

    /** @return array<string, mixed> */
    public function eventPayload(): array;
}
