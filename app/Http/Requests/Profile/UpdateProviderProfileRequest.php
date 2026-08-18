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
            'category_ids' => ['sometimes', 'array', 'min:1', 'max:3'],
            'category_ids.*' => ['integer', 'distinct', 'exists:categories,id'],
            'title' => ['sometimes', 'nullable', 'string', 'max:150'],
            'bio' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'audio_intro_url' => ['sometimes', 'nullable', 'string', 'max:255'],
            'latitude' => ['sometimes', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'numeric', 'between:-180,180'],
            'city' => ['sometimes', 'nullable', 'string', 'max:100'],
            'district' => ['sometimes', 'nullable', 'string', 'max:100'],
            'city_id' => ['sometimes', 'nullable', 'exists:cities,id'],
            'district_id' => ['sometimes', 'nullable', 'exists:districts,id'],
            'is_active' => ['sometimes', 'boolean'],
            'full_this_week' => ['sometimes', 'boolean'],
            'quiet_hours_start' => ['nullable', 'date_format:H:i'],
            'quiet_hours_end' => ['nullable', 'date_format:H:i'],
            'schedules' => ['sometimes', 'array'],
            'schedules.*.day_of_week' => ['required_with:schedules', 'integer', 'between:1,7'],
            'schedules.*.time_slot' => ['required_with:schedules', 'in:morning,afternoon,evening,night'],
            'schedules.*.is_available' => ['boolean'],
        ];
    }
}
