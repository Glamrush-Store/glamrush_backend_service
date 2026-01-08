<?php

namespace App\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasUlids;

    public $incrementing = false;
    protected $fillable = [
        'id',
        'name',
        'slug',
        'description',
        'parent_id',
        'meta_title',
        'meta_description',
        'sort_order',
        'is_active'
    ];
    protected $keyType = 'string';

    protected $table = 'categories';

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }
}
