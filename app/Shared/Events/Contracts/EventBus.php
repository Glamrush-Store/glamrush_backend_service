<?php

namespace App\Shared\Events\Contracts;

interface EventBus
{
    public function publish(DomainEvent $event, ?string $messageId = null): void;
}
