<?php

namespace App\Infrastructure\Persistence\Eloquent\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

final class NotExpiredScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $builder->where('expires_at', '>', now());
    }
}
