<?php

namespace App\Infrastructure\Events\Kafka;

use App\Shared\Events\Contracts\EventConsumer;
use App\Shared\Events\EventEnvelope;
use RdKafka\KafkaConsumer;
use RuntimeException;

final readonly class KafkaEventConsumer implements EventConsumer
{
    public function __construct(private array $config) {}

    public function consume(callable $handler, int $maxMessages = 0): void
    {
        $consumer = new KafkaConsumer(KafkaConfiguration::consumer($this->config));
        $consumer->subscribe([$this->config['topic']]);
        $processed = 0;

        while ($maxMessages === 0 || $processed < $maxMessages) {
            $message = $consumer->consume($this->config['consume_timeout_ms']);

            switch ($message->err) {
                case RD_KAFKA_RESP_ERR_NO_ERROR:
                    $handler(EventEnvelope::fromJson((string) $message->payload));
                    $consumer->commit($message);
                    $processed++;
                    break;
                case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                case RD_KAFKA_RESP_ERR__TIMED_OUT:
                    break;
                default:
                    throw new RuntimeException($message->errstr(), $message->err);
            }
        }
    }
}
