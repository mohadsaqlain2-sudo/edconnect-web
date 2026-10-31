<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for editing an existing post (FR-8).
 * Same rules as StorePostRequest; the ownership check lives in PostPolicy.
 */
class UpdatePostRequest extends FormRequest
{
    /**
     * Only the author (or an administrator) may edit the post.
     * The controller repeats this with $this->authorize('update', $post).
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('post')) ?? false;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:280'],
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
            'body.required' => __('A post cannot be empty.'),
            'body.max' => __('A post can be at most 280 characters long.'),
            'image.max' => __('The image must be smaller than 2 MB.'),
            'image.mimes' => __('Only JPG, PNG and WebP images are allowed.'),
        ];
    }
}
