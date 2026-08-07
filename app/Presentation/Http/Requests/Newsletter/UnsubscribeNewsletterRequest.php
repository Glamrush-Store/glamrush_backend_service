<?php

namespace App\Presentation\Http\Requests\Newsletter;

use Illuminate\Foundation\Http\FormRequest;

final class UnsubscribeNewsletterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['token' => ['required', 'string', 'size:64']];
    }
}
