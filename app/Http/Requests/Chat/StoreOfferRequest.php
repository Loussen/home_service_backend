<?php

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class StoreOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'scheduled_at' => ['required', 'date', 'after:now'],
            'duration_hours' => ['nullable', 'numeric', 'min:0.5', 'max:24'],
            'price_azn' => ['required', 'numeric', 'min:1', 'max:9999'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
