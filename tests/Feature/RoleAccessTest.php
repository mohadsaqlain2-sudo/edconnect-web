<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A student can never open the admin dashboard (FR-5).
     */
    public function test_a_student_cannot_open_the_admin_dashboard(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)
            ->get('/admin/dashboard')
            ->assertForbidden();
    }

    /**
     * Each role reaches its own dashboard (FR-2).
     */
    public function test_each_role_reaches_its_own_dashboard(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/dashboard')
            ->assertOk();

        $this->actingAs(User::factory()->teacher()->create())
            ->get('/teacher/dashboard')
            ->assertOk();

        $this->actingAs(User::factory()->student()->create())
            ->get('/student/dashboard')
            ->assertOk();
    }

    /**
     * /dashboard forwards everybody to the dashboard of their own role (FR-2).
     */
    public function test_the_generic_dashboard_redirects_by_role(): void
    {
        $this->actingAs(User::factory()->teacher()->create())
            ->get('/dashboard')
            ->assertRedirect('/teacher/dashboard');
    }

    /**
     * Guests are sent to the login page.
     */
    public function test_a_guest_cannot_open_the_admin_dashboard(): void
    {
        $this->get('/admin/dashboard')->assertRedirect('/login');
    }
}
