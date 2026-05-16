<?php
/*
 * © 2026 Demilade Oyewusi
 * Licensed under the MIT License.
 * See the LICENSE file for details.
 */

namespace App\Presentation\Http\Requests\Cart;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

final class CheckOutCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shipping_rate_id' => ['required', 'string', 'exists:shipping_rates,id'],
            'payment_method' => ['required', 'string', Rule::in(['paystack', 'flutterwave', 'pay_on_delivery'])],

            'shipping_address' => ['required', 'array'],
            'shipping_address.full_name' => ['required', 'string', 'max:150'],
            'shipping_address.email' => ['required', 'email', 'max:255'],
            'shipping_address.phone' => ['required', 'string', 'max:30'],
            'shipping_address.country' => ['required', 'string', 'max:100'],
            'shipping_address.state' => ['required', 'string', 'max:100'],
            'shipping_address.city' => ['required', 'string', 'max:100'],
            'shipping_address.postal_code' => ['nullable', 'string', 'max:20'],
            'shipping_address.line1' => ['required', 'string', 'max:255'],
            'shipping_address.line2' => ['nullable', 'string', 'max:255'],

            'billing_address' => ['nullable', 'array'],
            'billing_address.same_as_shipping' => ['nullable', 'boolean'],
            'billing_address.full_name' => ['required_if:billing_address.same_as_shipping,false', 'string', 'max:150'],
            'billing_address.email' => ['required_if:billing_address.same_as_shipping,false', 'email', 'max:255'],
            'billing_address.phone' => ['required_if:billing_address.same_as_shipping,false', 'string', 'max:30'],
            'billing_address.country' => ['required_if:billing_address.same_as_shipping,false', 'string', 'max:100'],
            'billing_address.state' => ['required_if:billing_address.same_as_shipping,false', 'string', 'max:100'],
            'billing_address.city' => ['required_if:billing_address.same_as_shipping,false', 'string', 'max:100'],
            'billing_address.postal_code' => ['nullable', 'string', 'max:20'],
            'billing_address.line1' => ['required_if:billing_address.same_as_shipping,false', 'string', 'max:255'],
            'billing_address.line2' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function shippingAddressPayload(): array
    {
        return $this->validated('shipping_address');
    }

    public function billingAddressPayload(): ?array
    {
        $billingAddress = $this->validated('billing_address') ?? null;

        if ($billingAddress === null) {
            return null;
        }

        if (($billingAddress['same_as_shipping'] ?? false) === true) {
            return $this->shippingAddressPayload();
        }

        return Arr::except($billingAddress, ['same_as_shipping']);
    }
}
