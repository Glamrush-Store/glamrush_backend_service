<?php

namespace App\Infrastructure\Events\RabbitMq;

use App\Shared\Events\Contracts\DomainEvent;
use App\Shared\Events\Contracts\EventBus;
use App\Shared\Events\EventEnvelope;
use PhpAmqpLib\Message\AMQPMessage;

final readonly class RabbitMqEventBus implements EventBus
{
    public function __construct(
        private RabbitMqConnectionFactory $connections,
        private array $config,
    ) {}

    public function publish(DomainEvent $event, ?string $messageId = null): void
    {
        $envelope = EventEnvelope::fromEvent($event, $messageId);
        $connection = $this->connections->connect();
        $channel = $connection->channel();

        try {
            $channel->exchange_declare(
                $this->config['exchange'],
                'topic',
                false,
                true,
                false,
            );
            $channel->queue_declare($this->config['queue'], false, true, false, false);
            $channel->queue_bind(
                $this->config['queue'],
                $this->config['exchange'],
                $this->config['binding_key'],
            );
            $channel->confirm_select();
            $channel->basic_publish(
                new AMQPMessage($envelope->toJson(), [
                    'content_type' => 'application/json',
                    'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                    'message_id' => $envelope->id,
                    'type' => $envelope->type,
                    'timestamp' => time(),
                ]),
                $this->config['exchange'],
                $this->routingKey($envelope->type),
                true,
            );
            $channel->wait_for_pending_acks($this->config['confirm_timeout']);
        } finally {
            $channel->close();
            $connection->close();
        }
    }

    private function routingKey(string $eventType): string
    {
        $prefix = trim((string) $this->config['routing_key_prefix'], '.');

        return $prefix === '' ? $eventType : "{$prefix}.{$eventType}";
    }
}
