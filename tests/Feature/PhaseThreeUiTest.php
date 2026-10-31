<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Phase 3: layout, reusable components, dashboards and profile pages
 * (FR-4, FR-5, FR-26, FR-29, FR-30, FR-31).
 */
class PhaseThreeUiTest extends TestCase
{
    use RefreshDatabase;

    /* ---------------------------------------------------------------
     | Layout and sidebar (FR-31)
     * --------------------------------------------------------------- */

    public function test_the_layout_remembers_the_sidebar_state_and_has_a_script_stack(): void
    {
        $teacher = User::factory()->teacher()->create();

        $response = $this->actingAs($teacher)->get(route('teacher.dashboard'));

        $response->assertOk()
            // Top navbar controls, including the desktop toggle.
            ->assertSee('data-sidebar-toggle', false)
            ->assertSee('data-bs-target="#appSidebar"', false)
            // The state is restored before the first paint and stored by layout.js.
            ->assertSee('educonnect.sidebar.collapsed', false)
            // The layout provides a footer and a scripts stack.
            ->assertSee('Learn together, anywhere.')
            ->assertDontSee('@stack', false);
    }

    public function test_each_role_gets_the_links_it_needs_in_the_sidebar(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Users')
            ->assertSee('Posts')
            ->assertSee('Classes')
            ->assertSee('Reports');

        $teacher = User::factory()->teacher()->create();
        $this->actingAs($teacher)->get(route('teacher.dashboard'))
            ->assertOk()
            ->assertSee('My Classes')
            ->assertSee('Create Class')
            ->assertSee('Feed')
            ->assertSee('My Profile')
            ->assertSee(route('users.show', $teacher), false);

        $student = User::factory()->student()->create();
        $this->actingAs($student)->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('Browse Classes')
            ->assertSee('My Classes')
            ->assertSee('Feed')
            ->assertSee('My Profile')
            ->assertSee(route('users.show', $student), false);
    }

    public function test_the_active_sidebar_link_is_marked_for_the_current_page(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('aria-current="page"', false);
    }

    public function test_a_page_can_set_its_own_title_and_breadcrumbs(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('profile.edit'))
            ->assertOk()
            // <title> uses the page title plus the app name.
            ->assertSee('<title>Profile settings · EduConnect</title>', false)
            // The layout renders the breadcrumb trail from the slot.
            ->assertSee('aria-label="Breadcrumb"', false)
            ->assertSee('breadcrumb-item active', false)
            ->assertSee('Profile settings');

        // The public profile uses the same slots.
        $this->actingAs($user)->get(route('users.show', $user))
            ->assertOk()
            ->assertSee('<title>My profile · EduConnect</title>', false)
            ->assertSee('aria-label="Breadcrumb"', false);
    }

    /* ---------------------------------------------------------------
     | Admin dashboard (FR-26)
     * --------------------------------------------------------------- */

    public function test_the_admin_dashboard_lists_the_newest_users(): void
    {
        $admin = User::factory()->admin()->create();
        $newest = User::factory()->teacher()->create(['name' => 'Newest Teacher']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk()
            ->assertSee('Recent users')
            ->assertSee('Newest Teacher')
            ->assertSee($newest->email);

        // The table is capped at five rows.
        $response->assertViewHas('recentUsers', fn ($users) => $users->count() <= 5);
    }

    /* ---------------------------------------------------------------
     | Teacher dashboard (FR-29)
     * --------------------------------------------------------------- */

    public function test_the_teacher_dashboard_has_class_actions_and_enrollment_activity(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create(['name' => 'Enrolled Student']);

        $classRoom = ClassRoom::factory()->scheduled()->create([
            'teacher_id' => $teacher->id,
            'title' => 'Algebra II',
        ]);
        $classRoom->students()->attach($student->id);

        $response = $this->actingAs($teacher)->get(route('teacher.dashboard'));

        $response->assertOk()
            ->assertSee('Actions')
            ->assertSee('Algebra II')
            ->assertSee(route('classes.edit', $classRoom), false)
            ->assertSee(route('classes.students', $classRoom), false)
            // The activity stream includes the new enrollment.
            ->assertSee('Enrolled Student')
            ->assertSee('enrolled in');
    }

    /* ---------------------------------------------------------------
     | Student dashboard (FR-30)
     * --------------------------------------------------------------- */

    public function test_the_student_join_button_is_disabled_until_the_class_is_live(): void
    {
        $student = User::factory()->student()->create();
        $teacher = User::factory()->teacher()->create();

        $scheduled = ClassRoom::factory()->scheduled()->create(['teacher_id' => $teacher->id]);
        $scheduled->students()->attach($student->id);

        $response = $this->actingAs($student)->get(route('student.dashboard'));

        $response->assertOk()
            ->assertSee('Join')
            ->assertSee('aria-disabled="true"', false)
            ->assertSee('disabled', false);
    }

    /* ---------------------------------------------------------------
     | Public profile (FR-5)
     * --------------------------------------------------------------- */

    public function test_the_public_profile_uses_the_canonical_users_url_and_keeps_u_as_a_redirect(): void
    {
        $viewer = User::factory()->student()->create();
        $profile = User::factory()->teacher()->create(['name' => 'Visible Teacher']);

        $this->actingAs($viewer)
            ->get('/users/'.$profile->id)
            ->assertOk()
            ->assertSee('Visible Teacher')
            ->assertSee('followers')
            ->assertSee('following')
            ->assertSee('Posts');

        $this->actingAs($viewer)
            ->get('/u/'.$profile->id)
            ->assertRedirect(route('users.show', $profile));
    }

    /* ---------------------------------------------------------------
     | Profile photo (FR-4)
     * --------------------------------------------------------------- */

    public function test_a_user_can_upload_and_remove_a_profile_photo(): void
    {
        Storage::fake('public');

        $user = User::factory()->teacher()->create();

        // Upload: the file is stored on the public disk and the column is set.
        $this->actingAs($user)->patch('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'subject' => 'Mathematics',
            'bio' => 'I like numbers.',
            'photo' => UploadedFile::fake()->image('avatar.jpg', 300, 300),
        ])->assertSessionHasNoErrors()->assertRedirect('/profile');

        $user->refresh();
        $this->assertNotNull($user->photo);
        Storage::disk('public')->assertExists($user->photo);

        // Remove: the checkbox clears the column and deletes the old file.
        $oldPhoto = $user->photo;

        $this->actingAs($user)->patch('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'remove_photo' => '1',
        ])->assertSessionHasNoErrors()->assertRedirect('/profile');

        $this->assertNull($user->refresh()->photo);
        Storage::disk('public')->assertMissing($oldPhoto);
    }

    public function test_the_profile_photo_is_validated_for_type_and_size(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        // A PDF is not an image.
        $this->actingAs($user)->from('/profile')->patch('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'photo' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
        ])->assertSessionHasErrors('photo');

        // A real image above the 2 MB limit is refused as well.
        $this->actingAs($user)->from('/profile')->patch('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'photo' => UploadedFile::fake()->image('huge.jpg')->size(3000),
        ])->assertSessionHasErrors('photo');

        $this->assertNull($user->refresh()->photo);
    }

    public function test_the_profile_form_shows_the_preview_and_remove_controls(): void
    {
        Storage::fake('public');
        $user = User::factory()->create([
            'photo' => 'profiles/existing.jpg',
        ]);
        Storage::disk('public')->put($user->photo, 'fake image');

        $this->actingAs($user)->get('/profile')
            ->assertOk()
            ->assertSee('data-photo-form', false)
            ->assertSee('data-photo-preview', false)
            ->assertSee('data-photo-input', false)
            ->assertSee('data-photo-remove', false)
            ->assertSee('Remove current photo');
    }

    /* ---------------------------------------------------------------
     | Reusable components
     * --------------------------------------------------------------- */

    public function test_the_reusable_blade_components_render(): void
    {
        $user = User::factory()->create(['name' => 'Component User']);

        $avatar = (string) Blade::render('<x-avatar :user="$user" size="lg" />', ['user' => $user]);
        $this->assertStringContainsString('avatar-lg', $avatar);
        $this->assertStringContainsString('Component User', $avatar);

        $badge = (string) Blade::render('<x-status-badge status="live" />');
        $this->assertStringContainsString('text-bg-danger', $badge);
        $this->assertStringContainsString('Live', $badge);

        $empty = (string) Blade::render('<x-empty-state icon="bi-inbox" title="Nothing here" message="Come back later." />');
        $this->assertStringContainsString('Nothing here', $empty);
        $this->assertStringContainsString('Come back later.', $empty);

        $modal = (string) Blade::render(
            '<x-confirm-delete-modal id="demo-delete" action="/demo" title="Delete demo?" message="Really?" />'
        );
        $this->assertStringContainsString('id="demo-delete"', $modal);
        $this->assertStringContainsString('method="POST"', $modal);
        $this->assertStringContainsString('name="_method" value="DELETE"', $modal);
    }
}
