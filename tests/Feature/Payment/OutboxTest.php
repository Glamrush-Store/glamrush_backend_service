<?php

use App\Domain\Order\Events\OrderPlaced;
use App\Infrastructure\Persistence\Eloquent\Models\OutboxMessage;
use App\Jobs\DeliverOutboxMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

it('delivers each outbox message once even when its job is retried', function () {
    Event::fake([OrderPlaced::class]);

    $message = OutboxMessage::query()->create([
        'event_key' => 'order:test-order:placed',
        'type' => 'order.placed',
        'payload' => ['order_id' => 'test-order'],
        'available_at' => now(),
    ]);

    $job = new DeliverOutboxMessage((string) $message->id);
    app()->call([$job, 'handle']);
    app()->call([$job, 'handle']);

    Event::assertDispatchedTimes(OrderPlaced::class, 1);
    expect($message->fresh()->processed_at)->not->toBeNull()
        ->and($message->fresh()->attempts)->toBe(1);
});
