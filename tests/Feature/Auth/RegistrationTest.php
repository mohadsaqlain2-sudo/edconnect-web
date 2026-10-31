<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A visitor can register as a teacher and lands on the teacher dashboard (FR-1, FR-2).
     */
    public function test_a_teacher_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'New Teacher',
            'email' => 'new.teacher@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'teacher',
            'subject' => 'Mathematics',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/teacher/dashboard');

        $this->assertDatabaseHas('users', [
            'email' => 'new.teacher@example.com',
            'role' => 'teacher',
            'subject' => 'Mathematics',
        ]);
    }

    /**
     * A visitor can register as a student and lands on the student dashboard (FR-1, FR-2).
     */
    public function test_a_student_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'New Student',
            'email' => 'new.student@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'student',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/student/dashboard');

        $this->assertDatabaseHas('users', [
            'email' => 'new.student@example.com',
            'role' => 'student',
            'subject' => null,
        ]);
    }

    /**
     * Nobody can create an admin account through the public form (FR-1).
     */
    public function test_the_admin_role_cannot_be_registered(): void
    {
        $response = $this->post('/register', [
            'name' => 'Sneaky User',
            'email' => 'sneaky@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'admin',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'sneaky@example.com']);
        $this->assertSame(0, User::where('role', User::ROLE_ADMIN)->count());
    }

    /**
     * The subject is optional: a teacher can add it later from the profile (FR-4).
     */
    public function test_a_teacher_can_register_without_a_subject(): void
    {
        $response = $this->post('/register', [
            'name' => 'No Subject Teacher',
            'email' => 'nosubject@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'teacher',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/teacher/dashboard');

        $this->assertDatabaseHas('users', [
            'email' => 'nosubject@example.com',
            'role' => 'teacher',
            'subject' => null,
        ]);
    }
}
