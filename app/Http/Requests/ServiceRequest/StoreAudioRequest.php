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
    }

    public function rules(): array
    {
        return [
            // Keep MIME loose — iOS record produces audio/mp4, video/mp4, etc.
            'audio' => ['required', 'file', 'max:15360'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'address' => ['nullable', 'string', 'max:255'],
            'is_urgent' => ['sometimes', 'boolean'],
        ];
    }
}
