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
     * Only the author may edit the post; administrators moderate but do not
     * rewrite somebody else's words. The controller repeats this with
     * $this->authorize('update', $post).
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('post')) ?? false;
    }

    /**
     * Trim the body before validation, so an edit to only spaces is rejected
     * instead of blanking out the post.
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
