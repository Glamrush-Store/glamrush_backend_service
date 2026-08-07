<?php

namespace App\Infrastructure\Events\RabbitMq;

use App\Shared\Events\Contracts\EventConsumer;
use App\Shared\Events\EventEnvelope;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage;
use Throwable;

final readonly class RabbitMqEventConsumer implements EventConsumer
{
    public function __construct(
        private RabbitMqConnectionFactory $connections,
        private array $config,
    ) {}

    public function consume(callable $handler, int $maxMessages = 0): void
    {
        $connection = $this->connections->connect();
        $channel = $connection->channel();
        $processed = 0;

        $channel->exchange_declare($this->config['exchange'], 'topic', false, true, false);
        $channel->queue_declare($this->config['queue'], false, true, false, false);
        $channel->queue_bind(
            $this->config['queue'],
            $this->config['exchange'],
            $this->config['binding_key'],
        );
        $channel->basic_qos(null, $this->config['prefetch_count'], null);
        $channel->basic_consume(
            $this->config['queue'],
            '',
            false,
            false,
            false,
            false,
            function (AMQPMessage $message) use ($handler, $maxMessages, &$processed): void {
                try {
                    $handler(EventEnvelope::fromJson($message->getBody()));
                    $message->ack();
                    $processed++;

                    if ($maxMessages > 0 && $processed >= $maxMessages) {
                        $message->getChannel()->stopConsume();
                    }
                } catch (Throwable $exception) {
                    $message->reject(true);
                    throw $exception;
                }
            },
        );

        try {
            while ($channel->is_consuming() && ($maxMessages === 0 || $processed < $maxMessages)) {
                try {
                    $channel->wait(null, false, 1);
                } catch (AMQPTimeoutException) {
                    continue;
                }
            }
        } finally {
            $channel->close();
            $connection->close();
        }
    }
}
