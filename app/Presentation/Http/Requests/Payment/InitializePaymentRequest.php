<?php

namespace App\Presentation\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class InitializePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => ['required', 'string', 'exists:orders,id'],
            'payment_method' => ['required', 'string', Rule::in(['paystack', 'flutterwave', 'pay_on_delivery'])],
        ];
    }
}
