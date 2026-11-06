<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation for creating a live class (FR-15).
 * A class may be planned for today or any later day, at any time of that day:
 * the teacher starts it manually whenever they are ready, so a start time that
 * has already passed today must never block the form (the teacher would be
 * pushed to tomorrow for no reason). Only whole days in the past are refused.
 */
class StoreClassRequest extends FormRequest
{
    /**
     * Only teachers create classes. The "role:teacher" route middleware is the
     * outer gate; this is the second one.
     */
    public function authorize(): bool
    {
        return $this->user()?->isTeacher() ?? false;
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
            'date' => ['required', 'date', 'after_or_equal:today'],
            'time' => ['required', 'date_format:H:i'],
            // A class lasts between 15 minutes and 4 hours.
            'duration' => ['required', 'integer', 'min:15', 'max:240'],
        ];
    }

    /**
     * The browser sends "14:30" from <input type="time">, but a form that was
     * filled in by hand may send "14:30:00". Cut both down to H:i.
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
            'date.after_or_equal' => __('The class cannot be planned in the past.'),
            'time.date_format' => __('Enter the start time as HH:MM.'),
            'duration.min' => __('A class lasts at least 15 minutes.'),
            'duration.max' => __('A class lasts at most 4 hours.'),
        ];
    }
}
