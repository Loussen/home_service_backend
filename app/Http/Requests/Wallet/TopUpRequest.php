<?php

namespace App\Http\Requests\Wallet;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TopUpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:1', 'max:5000'],
            'payment_method' => ['sometimes', Rule::in(['card', 'terminal'])],
            'reference' => ['nullable', 'string', 'max:100'],
        ];
    }
}
