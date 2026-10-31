<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for a new comment (FR-10).
 * The body is required and capped at 280 characters - the same limit as a post.
 */
class StoreCommentRequest extends FormRequest
{
    /**
     * Only somebody who can see the post may comment on it. The policy also
     * refuses hidden posts, so a moderated post cannot collect new comments.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('view', $this->route('post')) ?? false;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:280'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'body.required' => __('Write something before commenting.'),
            'body.max' => __('A comment can be at most 280 characters long.'),
        ];
    }
}
