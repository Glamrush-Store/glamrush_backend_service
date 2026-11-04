# Swappable domain event bus

The application publishes typed domain events through
`App\Shared\Events\Contracts\EventBus`. The active transport is selected with one environment value:

```dotenv
EVENT_BUS_DRIVER=laravel
```

Supported values are `laravel`, `rabbitmq`, and `kafka`. Application services and outbox jobs do not
depend on a concrete transport.

## Delivery model

Order, payment, and registration events are written to `outbox_messages` before delivery. The existing
`outbox:dispatch` scheduler dispatches `DeliverOutboxMessage`, which publishes through the selected bus.

- The Laravel driver dispatches the typed event directly to Laravel's event dispatcher.
- RabbitMQ publishes a persistent JSON message to a durable topic exchange and waits for publisher confirms.
- Kafka publishes the same JSON envelope with `acks=all` and idempotent production enabled.

RabbitMQ and Kafka are at-least-once transports. The inbound worker claims each envelope ID in the
`consumed_events` inbox table before dispatching it, so ordinary broker redeliveries do not dispatch an event
twice. Event handlers should still keep irreversible external side effects idempotent. The outbox message UUID
is preserved as the RabbitMQ message ID or Kafka record key.

Every external message uses this versioned envelope:

```json
{
  "id": "outbox-message-uuid",
  "type": "order.paid",
  "payload": { "order_id": "order-uuid" },
  "occurred_at": "2026-08-07T12:00:00+00:00",
  "schema_version": 1
}
```

## Laravel

```dotenv
EVENT_BUS_DRIVER=laravel
```

No separate event consumer is required. The normal queue worker is still required for queued listeners:

```bash
php artisan queue:work
```

## RabbitMQ

The project includes `php-amqplib/php-amqplib`. Start a local broker with:

```bash
docker compose -f docker-compose.events.yml up -d rabbitmq
```

Configure the application:

```dotenv
EVENT_BUS_DRIVER=rabbitmq
RABBITMQ_HOST=127.0.0.1
RABBITMQ_PORT=5672
RABBITMQ_USERNAME=guest
RABBITMQ_PASSWORD=guest
RABBITMQ_VHOST=/
RABBITMQ_EVENT_EXCHANGE=glamrush.events
RABBITMQ_EVENT_QUEUE=glamrush-backend.events
RABBITMQ_ROUTING_KEY_PREFIX=glamrush
RABBITMQ_BINDING_KEY=glamrush.#
```

Run the inbound event worker as a supervised long-running process:

```bash
php artisan events:consume
```

The exchange and queue are declared automatically. TLS certificate settings are available in
`config/event_bus.php`.

## Kafka

Kafka requires `librdkafka` and the PHP `rdkafka` extension. The production Dockerfile installs both.
For a non-container PHP installation, install those native dependencies before selecting this driver.

Start a local broker:

```bash
docker compose -f docker-compose.events.yml up -d kafka
```

Configure and consume:

```dotenv
EVENT_BUS_DRIVER=kafka
KAFKA_BROKERS=127.0.0.1:9092
KAFKA_EVENT_TOPIC=glamrush.events
KAFKA_CONSUMER_GROUP=glamrush-backend
KAFKA_AUTO_OFFSET_RESET=earliest
```

```bash
php artisan events:consume
```

SASL and TLS settings are available in `.env.example`. Kafka offsets are committed only after the typed
event has been dispatched to Laravel.

## Switching drivers

Change `EVENT_BUS_DRIVER`, clear cached configuration, and restart the queue, scheduler, Octane, and event
consumer processes:

```bash
php artisan optimize:clear
php artisan queue:restart
```

For RabbitMQ or Kafka, ensure `php artisan events:consume` is supervised. For Laravel, stop that worker.
The queue worker and scheduler remain required for every driver.

## Adding an event

1. Implement `DomainEvent` on the event class.
2. Add its type-to-class mapping to `DomainEventRegistry`.
3. Record it with `OutboxService::recordEvent()`.
4. Add a Laravel listener normally; automatic event discovery will register it.
5. Add a serialization round-trip test.
