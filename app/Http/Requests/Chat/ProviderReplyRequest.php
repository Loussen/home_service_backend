<?php

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class ProviderReplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service_request_id' => ['required', 'integer', 'exists:service_requests,id'],
            'provider_profile_id' => ['nullable', 'integer', 'exists:provider_profiles,id'],
            'message' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
