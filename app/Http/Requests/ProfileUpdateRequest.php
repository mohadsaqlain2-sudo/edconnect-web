<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->user()->id),
            ],

            // Photo: real image files only, max 2 MB (FR-4).
            'photo' => [
                'nullable',
                'image',
                'mimes:'.implode(',', config('educonnect.uploads.profile_photo_mimes')),
                'max:'.config('educonnect.uploads.profile_photo_max_kb'),
            ],

            // The "remove current photo" checkbox (FR-4).
            'remove_photo' => ['sometimes', 'boolean'],

            'bio' => ['nullable', 'string', 'max:1000'],

            // Only teachers see this field in the form; it stays nullable for students.
            'subject' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * Custom messages that name the file size limit in a friendly way.
     */
    public function messages(): array
    {
        return [
            'photo.max' => 'The photo may not be larger than 2 MB.',
            'photo.mimes' => 'The photo must be a JPG, PNG or WEBP file.',
        ];
    }
}
