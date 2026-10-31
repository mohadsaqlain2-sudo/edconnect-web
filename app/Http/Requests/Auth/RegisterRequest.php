<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    /**
     * Everyone is allowed to register.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for creating a new account.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Password::defaults()],

            // Only teacher or student. A tampered "admin" value is rejected here,
            // so nobody can promote themselves through the public form.
            'role' => ['required', Rule::in(User::REGISTERABLE_ROLES)],

            // The subject is optional at registration (teachers can add or change
            // it later from their profile, FR-4).
            'subject' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * Friendlier messages for the most common mistakes.
     */
    public function messages(): array
    {
        return [
            'role.in' => 'Please choose either Teacher or Student.',
        ];
    }

    /**
     * Extra safety net: even if someone crafts a request by hand, an "admin"
     * role is thrown away before the controller ever sees it.
     */
    protected function passedValidation(): void
    {
        if (! in_array($this->string('role')->toString(), User::REGISTERABLE_ROLES, true)) {
            abort(403, 'This role cannot be registered.');
        }
    }
}
