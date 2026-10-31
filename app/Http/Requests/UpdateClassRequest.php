<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for editing an existing class (FR-16).
 *
 * Difference to StoreClassRequest: there is no "must be in the future" check.
 * A teacher has to be able to fix a typo in the title of a class that already
 * happened without being forced to move its date.
 */
class UpdateClassRequest extends FormRequest
{
    /**
     * Only the owning teacher (or an admin) may edit the class. ClassPolicy
     * makes that decision; the controller repeats the check with authorize().
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('classRoom')) ?? false;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'subject' => ['nullable', 'string', 'max:100'],
            'date' => ['required', 'date'],
            'time' => ['required', 'date_format:H:i'],
            // A class lasts between 15 minutes and 4 hours.
            'duration' => ['required', 'integer', 'min:15', 'max:240'],
        ];
    }

    /**
     * Accept both "14:30" and "14:30:00".
     */
    protected function prepareForValidation(): void
    {
        if ($this->filled('time')) {
            $this->merge(['time' => substr((string) $this->input('time'), 0, 5)]);
        }
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => __('Give your class a title.'),
            'time.date_format' => __('Enter the start time as HH:MM.'),
            'duration.min' => __('A class lasts at least 15 minutes.'),
            'duration.max' => __('A class lasts at most 4 hours.'),
        ];
    }
}
