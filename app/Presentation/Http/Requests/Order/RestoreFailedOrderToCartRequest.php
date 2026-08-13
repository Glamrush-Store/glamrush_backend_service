<?php

namespace App\Presentation\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

final class RestoreFailedOrderToCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'replace_cart' => ['sometimes', 'boolean'],
        ];
    }
}
