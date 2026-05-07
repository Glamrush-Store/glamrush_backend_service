<?php

namespace App\Presentation\Http\Requests\Cart;

use Illuminate\Foundation\Http\FormRequest;

final class MergeCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cart_token' => ['required', 'uuid'],
        ];
    }
}
