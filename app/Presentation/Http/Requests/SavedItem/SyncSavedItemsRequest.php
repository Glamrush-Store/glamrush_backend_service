<?php

namespace App\Presentation\Http\Requests\SavedItem;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncSavedItemsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'product_ids' => ['required', 'array', 'max:200'],
            'product_ids.*' => ['string', Rule::exists('products', 'id')->whereNull('deleted_at')],
        ];
    }
}
