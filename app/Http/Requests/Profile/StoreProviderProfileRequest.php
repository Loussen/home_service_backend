<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class StoreProviderProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required_without:category_ids', 'nullable', 'exists:categories,id'],
            'category_ids' => ['required_without:category_id', 'array', 'min:1', 'max:3'],
            'category_ids.*' => ['integer', 'distinct', 'exists:categories,id'],
            'title' => ['nullable', 'string', 'max:150'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'audio_intro_url' => ['nullable', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'city' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'district_id' => ['nullable', 'exists:districts,id'],
            'schedules' => ['nullable', 'array'],
            'schedules.*.day_of_week' => ['required_with:schedules', 'integer', 'between:1,7'],
            'schedules.*.time_slot' => ['required_with:schedules', 'in:morning,afternoon,evening,night'],
            'schedules.*.is_available' => ['boolean'],
        ];
    }
}
