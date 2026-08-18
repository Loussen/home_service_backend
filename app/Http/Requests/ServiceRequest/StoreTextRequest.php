<?php

namespace App\Http\Requests\ServiceRequest;

use Illuminate\Foundation\Http\FormRequest;

class StoreTextRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'text' => ['required', 'string', 'max:2000'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'address' => ['nullable', 'string', 'max:255'],
            'is_urgent' => ['sometimes', 'boolean'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
            'time_slot' => ['nullable', 'string', 'in:morning,afternoon,evening,night'],
        ];
    }
}
