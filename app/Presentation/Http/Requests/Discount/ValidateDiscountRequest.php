<?php

namespace App\Presentation\Http\Requests\Discount;

use Illuminate\Foundation\Http\FormRequest;

final class ValidateDiscountRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    protected function prepareForValidation(): void
    {
        $this->merge(['code' => strtoupper(trim((string) $this->input('code')))]);
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:64', 'regex:/^[A-Z0-9_-]+$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'shipping_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
