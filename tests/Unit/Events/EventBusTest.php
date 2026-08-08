<?php

use App\Domain\Order\Events\OrderPaid;
use App\Domain\Order\Events\OrderPendingOnDelivery;
use App\Domain\Order\Events\OrderPlaced;
use App\Domain\Payment\Events\PaymentFailed;
use App\Domain\User\Events\UserRegistered;
use App\Infrastructure\Events\Kafka\KafkaEventBus;
use App\Infrastructure\Events\Kafka\KafkaEventConsumer;
use App\Infrastructure\Events\Laravel\LaravelEventBus;
use App\Infrastructure\Events\RabbitMq\RabbitMqEventBus;
use App\Infrastructure\Events\RabbitMq\RabbitMqEventConsumer;
use App\Infrastructure\Persistence\Eloquent\Models\ConsumedEvent;
use App\Shared\Events\Contracts\EventBus;
use App\Shared\Events\Contracts\EventConsumer;
use App\Shared\Events\DomainEventRegistry;
use App\Shared\Events\EventEnvelope;
use App\Shared\Events\LocalEventDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

it('round trips every registered domain event through an envelope', function ($event) {
    $envelope = EventEnvelope::fromEvent($event, 'message-123');
    $decoded = EventEnvelope::fromJson($envelope->toJson());
    $restored = app(DomainEventRegistry::class)->eventFromEnvelope($decoded);

    expect($decoded->id)->toBe('message-123')
        ->and($decoded->type)->toBe($event->eventType())
        ->and($decoded->payload)->toBe($event->eventPayload())
        ->and($restored)->toEqual($event);
})->with([
    'order placed' => new OrderPlaced('order-1'),
    'order paid' => new OrderPaid('order-1'),
    'order pending on delivery' => new OrderPendingOnDelivery('order-1', 'payment-1'),
    'payment failed' => new PaymentFailed('payment-1'),
    'user registered' => new UserRegistered('user-1'),
]);

it('dispatches through Laravel when the Laravel driver is selected', function () {
    Event::fake([OrderPlaced::class]);
    config()->set('event_bus.default', 'laravel');
    app()->forgetInstance(EventBus::class);

    $bus = app(EventBus::class);
    $bus->publish(new OrderPlaced('order-1'), 'message-1');

    expect($bus)->toBeInstanceOf(LaravelEventBus::class);
    Event::assertDispatchedTimes(OrderPlaced::class, 1);
});

it('resolves both sides of each configured event transport', function (
    string $driver,
    string $busClass,
    string $consumerClass,
) {
    config()->set('event_bus.default', $driver);
    app()->forgetInstance(EventBus::class);
    app()->forgetInstance(EventConsumer::class);

    expect(app(EventBus::class))->toBeInstanceOf($busClass)
        ->and(app(EventConsumer::class))->toBeInstanceOf($consumerClass);
})->with([
    'rabbitmq' => ['rabbitmq', RabbitMqEventBus::class, RabbitMqEventConsumer::class],
    'kafka' => ['kafka', KafkaEventBus::class, KafkaEventConsumer::class],
]);

it('does not require a consumer worker for the Laravel driver', function () {
    config()->set('event_bus.default', 'laravel');
    app()->forgetInstance(EventConsumer::class);

    $this->artisan('events:consume')
        ->expectsOutputToContain('no event consumer is required')
        ->assertSuccessful();
});

it('dispatches a broker envelope only once when it is redelivered', function () {
    Event::fake([OrderPlaced::class]);
    $envelope = EventEnvelope::fromEvent(new OrderPlaced('order-1'), 'message-1');
    $dispatcher = app(LocalEventDispatcher::class);

    expect($dispatcher->dispatch($envelope))->toBeTrue()
        ->and($dispatcher->dispatch($envelope))->toBeFalse()
        ->and(ConsumedEvent::query()->count())->toBe(1);

    Event::assertDispatchedTimes(OrderPlaced::class, 1);
});
