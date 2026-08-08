<?php

namespace App\Presentation\Http\Requests\Content;

use Illuminate\Foundation\Http\FormRequest;

final class ListPublicFaqsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'category' => $this->filled('category') ? strtolower(trim((string) $this->input('category'))) : null,
            'search' => $this->filled('search') ? trim((string) $this->input('search')) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'category' => ['nullable', 'string', 'max:160', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'search' => ['nullable', 'string', 'min:2', 'max:120'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'between:1,50'],
        ];
    }
}
