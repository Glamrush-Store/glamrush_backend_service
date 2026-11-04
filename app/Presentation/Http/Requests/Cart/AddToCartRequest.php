<?php

namespace App\Presentation\Http\Requests\Cart;

use Illuminate\Foundation\Http\FormRequest;

final class AddToCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'string', 'exists:products,id'],
            'product_variant_id' => ['nullable', 'string', 'exists:product_variants,id'],
            'quantity' => ['integer', 'min:1', 'max:999'],
        ];
    }
}
