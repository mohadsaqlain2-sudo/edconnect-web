<?php

namespace App\Http\Requests;

use App\Models\ClassRoom;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for the scheduled -> live -> completed switch (FR-21).
 * Only the owning teacher may change the status; ClassPolicy enforces it.
 */
class UpdateClassStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('changeStatus', $this->route('classRoom')) ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(ClassRoom::STATUSES)],
        ];
    }
}
