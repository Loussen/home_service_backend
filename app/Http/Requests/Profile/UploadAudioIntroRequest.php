<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UploadAudioIntroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'audio' => ['required', 'file', 'mimes:m4a,mp3,wav,aac,mpeg,mp4,x-m4a,webm,ogg,opus', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'audio.max' => 'Audio maksimum ~20 saniyə / 5MB ola bilər.',
        ];
    }
}
