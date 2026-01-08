<?php

namespace App\Shared\Filters\Purity;

use Abbasudo\Purity\Filters\Filter;
use Closure;
use Illuminate\Database\Eloquent\Builder;

final class ProductSearchFilter extends Filter
{
    /**
     * Operator string to detect in the query params.
     */
    protected static string $operator = '$like';

    /**
     * Apply filter logic.
     */
    public function apply(): Closure
    {
        return function (Builder $query) {
            foreach ($this->values as $value) {
                $query->where(function ($q) use ($value) {
                    $q->where('products.name', 'ILIKE', "%{$value}%")
                        ->orWhere('products.slug', 'ILIKE', "%{$value}%");
                });
            }
        };
    }
}
