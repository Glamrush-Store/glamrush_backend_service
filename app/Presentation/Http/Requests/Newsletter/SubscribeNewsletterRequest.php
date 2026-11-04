<?php

namespace App\Presentation\Http\Requests\Newsletter;

use Illuminate\Foundation\Http\FormRequest;

final class SubscribeNewsletterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['email' => mb_strtolower(trim((string) $this->input('email')))]);
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
            'source' => ['nullable', 'string', 'max:100'],
        ];
    }
}
