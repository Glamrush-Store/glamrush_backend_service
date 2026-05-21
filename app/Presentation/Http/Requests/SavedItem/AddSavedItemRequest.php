<?php

namespace App\Presentation\Http\Requests\SavedItem;

use Illuminate\Foundation\Http\FormRequest;

class AddSavedItemRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'string', 'exists:products,id'],
        ];
    }
}
