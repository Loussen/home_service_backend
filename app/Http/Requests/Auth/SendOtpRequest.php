<?php

namespace App\Http\Requests\Auth;

use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;

class SendOtpRequest extends FormRequest
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
                        $ops = implode(', ', PhoneNumber::operatorCodes());
                        $fail(
                            'Telefon formatı yanlışdır. '
                            .PhoneNumber::prefix()
                            ." + operator ($ops) + "
                            .PhoneNumber::subscriberDigits()
                            .' rəqəm.'
                        );
                    }
                },
            ],
        ];
    }
}
