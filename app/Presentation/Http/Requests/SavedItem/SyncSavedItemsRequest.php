<?php

namespace App\Presentation\Http\Requests\SavedItem;

use Illuminate\Foundation\Http\FormRequest;

class SyncSavedItemsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'product_ids'   => ['required', 'array', 'max:200'],
            'product_ids.*' => ['string', 'exists:products,id'],
        ];
    }
}
