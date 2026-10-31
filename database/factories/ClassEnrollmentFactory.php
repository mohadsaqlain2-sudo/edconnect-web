<?php

namespace Database\Factories;

use App\Models\ClassEnrollment;
use App\Models\ClassRoom;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClassEnrollment>
 */
class ClassEnrollmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'class_id' => ClassRoom::factory(),
            // Only students enroll in classes.
            'student_id' => User::factory()->student(),
        ];
    }
}
