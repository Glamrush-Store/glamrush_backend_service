<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Setting extends Model
{
    use HasUlids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['setting_category_id', 'key', 'value', 'value_type', 'description', 'is_public', 'is_active'];

    protected function casts(): array
    {
        return ['value' => 'array', 'is_public' => 'boolean', 'is_active' => 'boolean'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(SettingCategory::class, 'setting_category_id');
    }

    public function decodedValue(): mixed
    {
        $raw = $this->value['value'] ?? null;

        return match ($this->value_type) {
            'boolean' => filter_var($raw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
            'integer' => $raw === null ? null : (int) $raw,
            'decimal' => $raw === null ? null : (float) $raw,
            'array', 'json' => $raw,
            default => $raw,
        };
    }
}
