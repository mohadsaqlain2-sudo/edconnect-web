<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        // FR-2: after login everybody lands on the dashboard of their own role.
        $response->assertRedirect(route('student.dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    /**
     * FR-2: every role is sent to the dashboard it is allowed to open.
     */
    public function test_an_admin_is_redirected_to_the_admin_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($admin);
        $response->assertRedirect(route('admin.dashboard', absolute: false));
    }

    public function test_a_teacher_is_redirected_to_the_teacher_dashboard(): void
    {
        $teacher = User::factory()->teacher()->create();

        $response = $this->post('/login', [
            'email' => $teacher->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($teacher);
        $response->assertRedirect(route('teacher.dashboard', absolute: false));
    }

    /**
     * FR-1/FR-2: the split-screen auth pages expose the password toggles.
     */
    public function test_the_auth_pages_use_the_branded_layout_and_password_toggles(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('edu-auth-brand', false)
            ->assertSee('data-password-toggle', false);

        $this->get('/register')
            ->assertOk()
            ->assertSee('data-role-select', false)
            ->assertSee('data-subject-field', false)
            ->assertSee('data-password-toggle', false)
            ->assertDontSee('value="admin"', false);
    }
}
