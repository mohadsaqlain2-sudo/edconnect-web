<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration form.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Create the account and log the new user in.
     * The role can only be "teacher" or "student": the Form Request already
     * rejected anything else, so an admin account can never be registered here.
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
        // $request->safe() only contains the fields listed in the Form Request.
        $validated = $request->safe()->only(['name', 'email', 'password', 'role', 'subject']);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'], // bcrypted by the model's "hashed" cast
            'role' => $validated['role'],
            // The subject only applies to teachers.
            'subject' => $validated['role'] === User::ROLE_TEACHER ? ($validated['subject'] ?? null) : null,
        ]);

        event(new Registered($user));

        Auth::login($user);

        // FR-2: every role lands on its own dashboard.
        return redirect(route($user->dashboardRoute(), absolute: false));
    }
}
