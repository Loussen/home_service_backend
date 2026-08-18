<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVerificationDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'document' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
            'document_type' => ['sometimes', 'string', Rule::in(['id_card', 'passport'])],
            'provider_profile_id' => ['nullable', 'integer', 'exists:provider_profiles,id'],
        ];
    }
}
