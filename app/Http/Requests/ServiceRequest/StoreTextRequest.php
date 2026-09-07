<?php

namespace App\Http\Requests\ServiceRequest;

use App\Support\RequestTtl;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTextRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('ttl_hours') && $this->input('ttl_hours') !== null && $this->input('ttl_hours') !== '') {
            $this->merge(['ttl_hours' => (int) $this->input('ttl_hours')]);
        }
    }

    public function rules(): array
    {
        return [
            'text' => ['required', 'string', 'max:2000'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'child_age' => ['nullable', 'integer', 'between:0,17'],
            'has_pet' => ['nullable', 'boolean'],
            'budget_max' => ['nullable', 'numeric', 'min:0'],
            'address' => ['nullable', 'string', 'max:255'],
            'is_urgent' => ['sometimes', 'boolean'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
            'time_slot' => ['nullable', 'string', 'in:morning,afternoon,evening,night'],
            'ttl_hours' => ['nullable', 'integer', Rule::in(RequestTtl::optionsHours())],
        ];
    }
}
