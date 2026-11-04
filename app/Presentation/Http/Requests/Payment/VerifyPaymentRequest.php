<?php

namespace App\Presentation\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class VerifyPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'provider' => ['required', 'string', Rule::in(['paystack', 'flutterwave'])],
            'transaction_id' => ['required', 'string'],
        ];
    }
}
