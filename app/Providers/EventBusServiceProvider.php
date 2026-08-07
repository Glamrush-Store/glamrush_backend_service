<?php

namespace App\Providers;

use App\Infrastructure\Events\Kafka\KafkaEventBus;
use App\Infrastructure\Events\Kafka\KafkaEventConsumer;
use App\Infrastructure\Events\Laravel\LaravelEventBus;
use App\Infrastructure\Events\Laravel\LaravelEventConsumer;
use App\Infrastructure\Events\RabbitMq\RabbitMqConnectionFactory;
use App\Infrastructure\Events\RabbitMq\RabbitMqEventBus;
use App\Infrastructure\Events\RabbitMq\RabbitMqEventConsumer;
use App\Shared\Events\Contracts\EventBus;
use App\Shared\Events\Contracts\EventConsumer;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

final class EventBusServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(EventBus::class, function (): EventBus {
            $driver = (string) config('event_bus.default', 'laravel');

            return match ($driver) {
                'laravel' => new LaravelEventBus($this->app->make(Dispatcher::class)),
                'rabbitmq' => new RabbitMqEventBus(
                    new RabbitMqConnectionFactory(config('event_bus.drivers.rabbitmq')),
                    config('event_bus.drivers.rabbitmq'),
                ),
                'kafka' => new KafkaEventBus(config('event_bus.drivers.kafka')),
                default => throw new InvalidArgumentException("Unsupported event bus driver [{$driver}]."),
            };
        });

        $this->app->singleton(EventConsumer::class, function (): EventConsumer {
            $driver = (string) config('event_bus.default', 'laravel');

            return match ($driver) {
                'laravel' => new LaravelEventConsumer,
                'rabbitmq' => new RabbitMqEventConsumer(
                    new RabbitMqConnectionFactory(config('event_bus.drivers.rabbitmq')),
                    config('event_bus.drivers.rabbitmq'),
                ),
                'kafka' => new KafkaEventConsumer(config('event_bus.drivers.kafka')),
                default => throw new InvalidArgumentException("Unsupported event bus driver [{$driver}]."),
            };
        });
    }
}
