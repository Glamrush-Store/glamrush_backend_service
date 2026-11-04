<?php

namespace App\Presentation\Http\Requests\SavedItem;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddSavedItemRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'string', Rule::exists('products', 'id')->whereNull('deleted_at')],
        ];
    }
}
