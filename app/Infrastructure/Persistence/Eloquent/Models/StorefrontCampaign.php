<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

final class StorefrontCampaign extends Model implements HasMedia
{
    use HasUlids, InteractsWithMedia;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'storefront_campaigns';

    protected $guarded = ['*'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'priority' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeCurrentlyScheduled(Builder $query, mixed $at = null): Builder
    {
        $at ??= now();

        return $query
            ->where(fn (Builder $builder) => $builder->whereNull('starts_at')->orWhere('starts_at', '<=', $at))
            ->where(fn (Builder $builder) => $builder->whereNull('ends_at')->orWhere('ends_at', '>', $at));
    }

    public function scopePublished(Builder $query, mixed $at = null): Builder
    {
        return $query->active()->currentlyScheduled($at);
    }

    public function scopeForStorefront(Builder $query, string $slug): Builder
    {
        return $query->where('storefront_slug', $slug);
    }

    public function scopeByPriority(Builder $query): Builder
    {
        return $query->orderByDesc('priority')->orderByDesc('updated_at');
    }
}
