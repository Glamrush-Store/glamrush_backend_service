<?php
/*
 * © 2026 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */


namespace App\Presentation\Http\Requests\Shipping;

use Illuminate\Foundation\Http\FormRequest;

class GetShippingOptionsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'country' => ['required', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:15'],
        ];
    }
}
