<?php

namespace App\Http\Requests\ServiceRequest;

use Illuminate\Foundation\Http\FormRequest;

class StoreAudioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_urgent')) {
            $this->merge([
                'is_urgent' => filter_var(
                    $this->input('is_urgent'),
                    FILTER_VALIDATE_BOOLEAN,
                    FILTER_NULL_ON_FAILURE
                ) ?? false,
            ]);
        }

        if ($this->has('latitude')) {
            $this->merge(['latitude' => (float) $this->input('latitude')]);
        }
        if ($this->has('longitude')) {
            $this->merge(['longitude' => (float) $this->input('longitude')]);
        }
        if ($this->has('has_pet')) {
            $this->merge([
                'has_pet' => filter_var(
                    $this->input('has_pet'),
                    FILTER_VALIDATE_BOOLEAN,
                    FILTER_NULL_ON_FAILURE
                ),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            // Keep MIME loose — iOS record produces audio/mp4, video/mp4, etc.
            'audio' => ['required', 'file', 'max:15360'],
            'duration_seconds' => ['required', 'numeric', 'min:5', 'max:20'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'address' => ['nullable', 'string', 'max:255'],
            'is_urgent' => ['sometimes', 'boolean'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'child_age' => ['nullable', 'integer', 'between:0,17'],
            'has_pet' => ['nullable', 'boolean'],
            'budget_max' => ['nullable', 'numeric', 'min:0'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
            'time_slot' => ['nullable', 'string', 'in:morning,afternoon,evening,night'],
        ];
    }

    public function messages(): array
    {
        return [
            'duration_seconds.min' => 'Səs ən azı 5 saniyə olmalıdır. Aydın danışıb yenidən göndərin.',
            'duration_seconds.max' => 'Səs ən çox 20 saniyə ola bilər.',
        ];
    }
}
