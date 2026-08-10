<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProviderProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['sometimes', 'exists:categories,id'],
            'title' => ['sometimes', 'nullable', 'string', 'max:150'],
            'bio' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'audio_intro_url' => ['sometimes', 'nullable', 'string', 'max:255'],
            'latitude' => ['sometimes', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'numeric', 'between:-180,180'],
            'city' => ['sometimes', 'nullable', 'string', 'max:100'],
            'district' => ['sometimes', 'nullable', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
            'schedules' => ['sometimes', 'array'],
            'schedules.*.day_of_week' => ['required_with:schedules', 'integer', 'between:1,7'],
            'schedules.*.time_slot' => ['required_with:schedules', 'in:morning,afternoon,evening,night'],
            'schedules.*.is_available' => ['boolean'],
        ];
    }
}
