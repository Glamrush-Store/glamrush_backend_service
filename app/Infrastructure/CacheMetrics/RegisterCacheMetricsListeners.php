<?php

namespace App\Infrastructure\CacheMetrics;

use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Cache\Events\KeyForgotten;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Support\Facades\Event;

final class RegisterCacheMetricsListeners
{
    public function register(): void
    {
        Event::listen(CacheHit::class, fn (CacheHit $event) => app(CacheMetricsRecorder::class)->record('hits', $event->key));
        Event::listen(CacheMissed::class, fn (CacheMissed $event) => app(CacheMetricsRecorder::class)->record('misses', $event->key));
        Event::listen(KeyWritten::class, fn (KeyWritten $event) => app(CacheMetricsRecorder::class)->record('writes', $event->key));
        Event::listen(KeyForgotten::class, fn (KeyForgotten $event) => app(CacheMetricsRecorder::class)->record('forgets', $event->key));
    }
}
