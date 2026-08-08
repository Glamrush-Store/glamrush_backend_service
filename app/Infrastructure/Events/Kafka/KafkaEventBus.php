<?php

namespace App\Infrastructure\Events\Kafka;

use App\Shared\Events\Contracts\DomainEvent;
use App\Shared\Events\Contracts\EventBus;
use App\Shared\Events\EventEnvelope;
use RdKafka\Producer;
use RuntimeException;

final readonly class KafkaEventBus implements EventBus
{
    public function __construct(private array $config) {}

    public function publish(DomainEvent $event, ?string $messageId = null): void
    {
        $envelope = EventEnvelope::fromEvent($event, $messageId);
        $producer = new Producer(KafkaConfiguration::producer($this->config));
        $topic = $producer->newTopic($this->config['topic']);

        $topic->produce(
            RD_KAFKA_PARTITION_UA,
            0,
            $envelope->toJson(),
            $envelope->id,
        );
        $producer->poll(0);

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $result = $producer->flush($this->config['flush_timeout_ms']);

            if ($result === RD_KAFKA_RESP_ERR_NO_ERROR) {
                return;
            }
        }

        throw new RuntimeException('Kafka did not acknowledge the event before the flush timeout.');
    }
}
