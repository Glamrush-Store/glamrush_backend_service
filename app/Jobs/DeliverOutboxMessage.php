<?php

namespace App\Jobs;

use App\Infrastructure\Persistence\Eloquent\Models\OutboxMessage;
use App\Shared\Events\Contracts\EventBus;
use App\Shared\Events\DomainEventRegistry;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Throwable;

final class DeliverOutboxMessage implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 10;

    public int $uniqueFor = 300;

    public function __construct(public readonly string $messageId) {}

    public function uniqueId(): string
    {
        return $this->messageId;
    }

    public function handle(EventBus $events, DomainEventRegistry $registry): void
    {
        try {
            DB::transaction(function () use ($events, $registry): void {
                $message = OutboxMessage::query()
                    ->whereKey($this->messageId)
                    ->lockForUpdate()
                    ->first();

                if ($message === null || $message->processed_at !== null) {
                    return;
                }

                $message->increment('attempts');
                $events->publish(
                    $registry->eventFrom($message->type, $message->payload),
                    (string) $message->id,
                );

                $message->update([
                    'processed_at' => now(),
                    'last_error' => null,
                ]);
            });
        } catch (Throwable $exception) {
            OutboxMessage::query()->whereKey($this->messageId)->increment('attempts', 1, [
                'last_error' => $exception->getMessage(),
                'available_at' => now()->addMinute(),
            ]);

            throw $exception;
        }
    }
}
