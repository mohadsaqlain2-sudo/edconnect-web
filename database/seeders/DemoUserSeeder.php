<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * The demo people. Phase 1 created them inside DemoSeeder; Phase 2 splits the
 * people from the content so DemoContentSeeder can run on its own.
 *
 * Every account uses the password "password" (hashed by the User model's
 * "hashed" cast). The admin comes from AdminUserSeeder.
 */
class DemoUserSeeder extends Seeder
{
    /** 3 teachers with realistic names, subjects and bios. */
    private const TEACHERS = [
        [
            'name' => 'Maria Rossi',
            'email' => 'teacher1@educonnect.test',
            'subject' => 'Mathematics',
            'bio' => 'Maths teacher for 12 years. I make algebra feel like a puzzle, not a punishment.',
        ],
        [
            'name' => 'David Chen',
            'email' => 'teacher2@educonnect.test',
            'subject' => 'Physics',
            'bio' => 'Physics and robotics. If it moves, we can measure it. Live experiments every Thursday.',
        ],
        [
            'name' => 'Amina Yusuf',
            'email' => 'teacher3@educonnect.test',
            'subject' => 'English',
            'bio' => 'Reader, writer and English teacher. Essays are conversations with a deadline.',
        ],
    ];

    /** 6 students. */
    private const STUDENTS = [
        ['name' => 'Sam Carter', 'email' => 'student1@educonnect.test'],
        ['name' => 'Lina Gomez', 'email' => 'student2@educonnect.test'],
        ['name' => 'Omar Haddad', 'email' => 'student3@educonnect.test'],
        ['name' => 'Nora Berg', 'email' => 'student4@educonnect.test'],
        ['name' => 'Yuki Tanaka', 'email' => 'student5@educonnect.test'],
        ['name' => 'Paul Dubois', 'email' => 'student6@educonnect.test'],
    ];

    public function run(): void
    {
        // updateOrCreate keeps the seeder safe to run twice.
        foreach (self::TEACHERS as $teacher) {
            User::updateOrCreate(
                ['email' => $teacher['email']],
                User::factory()->teacher()->make($teacher)->getAttributes()
            );
        }

        foreach (self::STUDENTS as $student) {
            User::updateOrCreate(
                ['email' => $student['email']],
                User::factory()->student()->make($student)->getAttributes()
            );
        }
    }
}
