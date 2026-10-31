<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    /**
     * Create the administrator account.
     * Admin users can never be created from the public registration form.
     *
     * The email, name and password come from the .env file - no secret is ever
     * hardcoded in the repository.
     */
    public function run(): void
    {
        $email = config('educonnect.admin.email');
        $password = config('educonnect.admin.password');

        // Fail loudly instead of creating an account with a weak/empty password.
        if (blank($password)) {
            throw new RuntimeException(
                'ADMIN_PASSWORD is not set. Add a strong password to your .env file and run the seeder again.'
            );
        }

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => config('educonnect.admin.name'),
                'password' => $password, // bcrypted by the model's "hashed" cast
                'role' => User::ROLE_ADMIN,
                'is_blocked' => false,
                'email_verified_at' => now(),
            ]
        );

        // Handy confirmation when running "php artisan db:seed".
        $this->command?->info("Admin account ready: {$email}");
    }
}
