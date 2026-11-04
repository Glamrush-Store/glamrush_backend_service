<?php

namespace App\Presentation\Http\Requests\Address;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label'         => ['sometimes', 'nullable', 'string', 'max:50'],
            'first_name'    => ['sometimes', 'string', 'max:100'],
            'last_name'     => ['sometimes', 'string', 'max:100'],
            'phone'         => ['sometimes', 'nullable', 'string', 'max:20'],
            'address_line_1'=> ['sometimes', 'string', 'max:255'],
            'address_line_2'=> ['sometimes', 'nullable', 'string', 'max:255'],
            'country'       => ['sometimes', 'string', 'max:100'],
            'state'         => ['sometimes', 'string', 'max:100'],
            'city'          => ['sometimes', 'string', 'max:100'],
            'postal_code'   => ['sometimes', 'string', 'max:20'],
            'is_default'    => ['sometimes', 'boolean'],
        ];
    }
}
