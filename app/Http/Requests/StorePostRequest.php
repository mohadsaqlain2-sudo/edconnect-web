<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for a new post (FR-7).
 * The text is required and capped at 280 characters, the image is optional
 * and only accepted as jpg / png / webp up to 2 MB.
 */
class StorePostRequest extends FormRequest
{
    /**
     * Any logged in user may publish a post.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Trim the body before validation, so a whitespace-only post fails the
     * "required" rule instead of being stored as invisible content.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('body'))) {
            $this->merge(['body' => trim($this->input('body'))]);
        }
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:280'],
            // "image" makes sure the upload really is an image, "mimes" pins the
            // allowed types and "max" is in kilobytes (2048 KB = 2 MB).
            // ImageUploadService decodes the file again before it is stored.
            'image' => [
                'nullable',
                'image',
                'mimes:'.implode(',', config('educonnect.uploads.post_image_mimes')),
                'max:'.config('educonnect.uploads.post_image_max_kb'),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'body.required' => __('Write something before publishing.'),
            'body.max' => __('A post can be at most 280 characters long.'),
            'image.max' => __('The image must be smaller than 2 MB.'),
            'image.mimes' => __('Only JPG, PNG and WebP images are allowed.'),
        ];
    }
}
