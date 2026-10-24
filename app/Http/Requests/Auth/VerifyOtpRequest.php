<?php

namespace App\Http\Requests\Auth;

use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('phone')) {
            $this->merge([
                'phone' => PhoneNumber::normalize((string) $this->input('phone')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'phone' => [
                'required',
                'string',
                'max:20',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_string($value) || ! PhoneNumber::isValid($value)) {
                        $fail('Telefon formatı yanlışdır.');
                    }
                },
            ],
            'code' => ['required', 'string', 'size:6'],
        ];
    }
}
