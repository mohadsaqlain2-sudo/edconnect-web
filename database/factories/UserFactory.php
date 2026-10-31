<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            // The User model has a "hashed" cast, so the plain value is bcrypted on save.
            'password' => 'password',
            'role' => User::ROLE_STUDENT,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * An admin account.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_ADMIN,
        ]);
    }

    /**
     * A teacher account (teachers also have a subject).
     */
    public function teacher(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_TEACHER,
            'subject' => fake()->randomElement(['Mathematics', 'Physics', 'English', 'History']),
            'bio' => fake()->sentence(12),
        ]);
    }

    /**
     * A student account.
     */
    public function student(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_STUDENT,
        ]);
    }

    /**
     * A blocked account: it may not log in (FR-6).
     */
    public function blocked(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_blocked' => true,
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
