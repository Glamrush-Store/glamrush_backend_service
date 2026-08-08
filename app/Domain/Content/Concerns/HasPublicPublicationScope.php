<?php

namespace App\Domain\Content\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasPublicPublicationScope
{
    public function scopePubliclyAvailable(Builder $query): Builder
    {
        return $query
            ->where('is_published', true)
            ->where(fn (Builder $q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }
}
