<?php

namespace App\Http\Requests\Wallet;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConfirmIapRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'platform' => ['required', Rule::in(['ios', 'android'])],
            'product_id' => ['required', 'string', 'max:128'],
            'purchase_id' => ['nullable', 'string', 'max:256'],
            'verification_data' => ['required', 'string', 'max:20000'],
            'source' => ['nullable', 'string', 'max:64'],
        ];
    }
}
