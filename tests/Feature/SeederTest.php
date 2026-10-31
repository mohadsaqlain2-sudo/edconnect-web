<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The seeder creates the admin account from the .env values (FR-1).
     */
    public function test_the_admin_seeder_creates_the_admin_account(): void
    {
        config()->set('educonnect.admin.password', 'SecretAdmin123!');

        $this->seed(AdminUserSeeder::class);

        $admin = User::where('email', config('educonnect.admin.email'))->first();

        $this->assertNotNull($admin);
        $this->assertSame(User::ROLE_ADMIN, $admin->role);
        $this->assertFalse($admin->is_blocked);
        $this->assertTrue(Hash::check('SecretAdmin123!', $admin->password));
    }

    /**
     * The seeder refuses to run without a password (no weak default accounts).
     */
    public function test_the_admin_seeder_requires_a_password(): void
    {
        config()->set('educonnect.admin.password', null);

        $this->expectException(\RuntimeException::class);

        $this->seed(AdminUserSeeder::class);
    }

    /**
     * The demo seeder creates 3 teachers and 6 students (FR-7).
     */
    public function test_the_demo_seeder_creates_three_teachers_and_six_students(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(3, User::where('role', User::ROLE_TEACHER)->count());
        $this->assertSame(6, User::where('role', User::ROLE_STUDENT)->count());
        $this->assertSame(1, User::where('role', User::ROLE_ADMIN)->count());
    }
}
