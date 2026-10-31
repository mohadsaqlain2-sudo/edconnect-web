<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

/**
 * Validation for creating a live class (FR-15).
 * A new class must be in the future - a teacher cannot schedule a lesson
 * in the past by accident.
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
     * Reject a class that would start in the past (FR-15).
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            // Only check the date/time combination when both parts are valid.
            if ($validator->errors()->has('date') || $validator->errors()->has('time')) {
                return;
            }

            $startsAt = $this->startsAt();

            if ($startsAt !== null && $startsAt->isPast()) {
                $validator->errors()->add('time', __('A new class has to start in the future.'));
            }
        });
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

    /**
     * The start moment, or null when the input cannot be understood.
     */
    private function startsAt(): ?Carbon
    {
        try {
            return Carbon::createFromFormat(
                'Y-m-d H:i',
                $this->input('date').' '.$this->input('time')
            );
        } catch (\Throwable) {
            return null;
        }
    }
}
