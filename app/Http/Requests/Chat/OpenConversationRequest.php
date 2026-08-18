<?php

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class OpenConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'provider_profile_id' => ['required', 'integer', 'exists:provider_profiles,id'],
            'service_request_id' => ['nullable', 'integer', 'exists:service_requests,id'],
            'message' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
