<?php

namespace App\Presentation\Http\Requests\Contact;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class StoreContactSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $normalize = fn (mixed $value) => is_string($value)
            ? trim((string) preg_replace("/\r\n?|\x{2028}|\x{2029}/u", "\n", $value))
            : $value;

        $this->merge([
            'name' => $normalize($this->input('name')),
            'email' => mb_strtolower((string) $normalize($this->input('email'))),
            'phone' => $normalize($this->input('phone')),
            'subject' => $normalize($this->input('subject')),
            'message' => $normalize($this->input('message')),
            'source' => $normalize($this->input('source')),
            'website' => $normalize($this->input('website')),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
            'phone' => ['nullable', 'string', 'regex:/^[+0-9() .-]{7,30}$/'],
            'subject' => ['nullable', 'string', 'max:180'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            'source' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9_-]+$/'],
            'website' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            foreach (['name', 'subject', 'message'] as $field) {
                $value = (string) $this->input($field, '');
                if ($value === '') {
                    continue;
                }

                if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', $value)) {
                    $validator->errors()->add($field, 'Control characters are not allowed.');
                }
                if (preg_match('/<\/?[a-z][^>]*>/i', $value)) {
                    $validator->errors()->add($field, 'HTML is not allowed.');
                }
                if (preg_match('/\b(?:javascript|vbscript|data):/i', $value)) {
                    $validator->errors()->add($field, 'Unsafe URL schemes are not allowed.');
                }
            }
        }];
    }
}
