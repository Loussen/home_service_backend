<?php

namespace App\Http\Requests\Moderation;

use App\Services\ModerationService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reported_user_id' => ['required', 'integer', 'exists:users,id'],
            'conversation_id' => ['nullable', 'integer', 'exists:conversations,id'],
            'reason' => ['required', 'string', Rule::in(ModerationService::REPORT_REASONS)],
            'details' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
