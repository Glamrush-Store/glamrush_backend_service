<?php

namespace App\Presentation\Http\Requests\Address;

use Illuminate\Foundation\Http\FormRequest;

class StoreAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label'         => ['nullable', 'string', 'max:50'],
            'first_name'    => ['required', 'string', 'max:100'],
            'last_name'     => ['required', 'string', 'max:100'],
            'phone'         => ['nullable', 'string', 'max:20'],
            'address_line_1'=> ['required', 'string', 'max:255'],
            'address_line_2'=> ['nullable', 'string', 'max:255'],
            'country'       => ['required', 'string', 'max:100'],
            'state'         => ['required', 'string', 'max:100'],
            'city'          => ['required', 'string', 'max:100'],
            'postal_code'   => ['required', 'string', 'max:20'],
            'is_default'    => ['nullable', 'boolean'],
        ];
    }
}
