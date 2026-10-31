<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Confirms the password before an account is deleted (FR-4).
 * Validation is reported in the "userDeletion" error bag, as the form expects.
 */
class DeleteAccountRequest extends FormRequest
{
    /**
     * The profile page renders the password error inside its deletion modal.
     *
     * @var string
     */
    protected $errorBag = 'userDeletion';

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'password' => ['required', 'current_password'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'password' => __('password'),
        ];
    }
}
