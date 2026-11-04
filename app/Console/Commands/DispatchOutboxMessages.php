<?php

namespace App\Console\Commands;

use App\Infrastructure\Persistence\Eloquent\Models\OutboxMessage;
use App\Jobs\DeliverOutboxMessage;
use Illuminate\Console\Command;

final class DispatchOutboxMessages extends Command
{
    protected $signature = 'outbox:dispatch {--limit=100}';

    protected $description = 'Dispatch pending transactional outbox messages';

    public function handle(): int
    {
        $limit = max(1, min(1000, (int) $this->option('limit')));

        $ids = OutboxMessage::query()
            ->whereNull('processed_at')
            ->where(fn ($query) => $query->whereNull('available_at')->orWhere('available_at', '<=', now()))
            ->orderBy('created_at')
            ->limit($limit)
            ->pluck('id');

        foreach ($ids as $id) {
            DeliverOutboxMessage::dispatch((string) $id);
        }

        $this->info("Dispatched {$ids->count()} outbox message(s).");

        return self::SUCCESS;
    }
}
