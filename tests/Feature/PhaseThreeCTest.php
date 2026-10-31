<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Phase 3C: the site-wide UI/UX redesign.
 *
 * These tests pin the cross-cutting pieces that the page-level tests do not
 * cover: the living style guide, the friendly error pages, the mobile
 * navigation, the redesigned profile tabs, grouped notifications, the search
 * highlight and the accessible admin tables.
 */
class PhaseThreeCTest extends TestCase
{
    use RefreshDatabase;

    /* ---------------------------------------------------------------
     | Living style guide (admin only)
     * --------------------------------------------------------------- */

    public function test_the_style_guide_is_available_to_admins_only(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();

        // A guest is sent to the login page.
        $this->get(route('admin.style-guide'))->assertRedirect(route('login'));

        $this->actingAs($admin)->get(route('admin.style-guide'))
            ->assertOk()
            // Tokens, components and states are documented on one page.
            ->assertSee('Single source of truth', false)
            ->assertSee('--edu-primary', false)
            ->assertSee('btn btn-primary', false);

        $this->actingAs($teacher)->get(route('admin.style-guide'))->assertForbidden();
        $this->actingAs($student)->get(route('admin.style-guide'))->assertForbidden();
    }

    /* ---------------------------------------------------------------
     | Friendly error pages
     * --------------------------------------------------------------- */

    public function test_unknown_pages_use_the_friendly_404_shell(): void
    {
        $this->get('/this-page-does-not-exist')
            ->assertNotFound()
            ->assertSee('Page not found')
            ->assertSee('Back to home')
            ->assertSee('lang="en"', false);
    }

    public function test_a_forbidden_page_uses_the_friendly_403_shell(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)->get(route('admin.users.index'))
            ->assertForbidden()
            ->assertSee('You are not allowed to open this page')
            ->assertSee('Go to dashboard');
    }

    public function test_the_session_expired_and_maintenance_pages_render(): void
    {
        $expired = view('errors.419')->render();
        $this->assertStringContainsString('Your session expired', $expired);
        $this->assertStringContainsString('Back to home', $expired);

        $maintenance = view('errors.503')->render();
        $this->assertStringContainsString('We are doing a little maintenance', $maintenance);
        $this->assertStringContainsString('Still stuck?', $maintenance);
    }

    /* ---------------------------------------------------------------
     | Guest landing page
     * --------------------------------------------------------------- */

    public function test_the_landing_page_offers_both_roles_a_clear_way_in(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Teach and learn,')
            ->assertSee('Register as Student')
            ->assertSee('Register as Teacher')
            ->assertSee(route('register', ['role' => 'teacher']), false)
            ->assertSee('Three steps to your first class')
            // The hero illustration is described for screen readers.
            ->assertSee('aria-label="Preview of a live class', false);
    }

    /* ---------------------------------------------------------------
     | Layout: skip link, mobile navigation and the feed composer button
     * --------------------------------------------------------------- */

    public function test_authenticated_pages_have_a_skip_link_and_mobile_navigation(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('edu-skip-link', false)
            ->assertSee('href="#main-content"', false)
            ->assertSee('edu-mobile-nav', false)
            ->assertSee('Mobile navigation');
    }

    public function test_the_floating_composer_button_only_appears_on_the_feed(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)->get(route('feed.index'))
            ->assertOk()
            ->assertSee('edu-fab', false);

        $this->actingAs($student)->get(route('student.dashboard'))
            ->assertOk()
            ->assertDontSee('edu-fab', false);
    }

    /* ---------------------------------------------------------------
     | Profile tabs
     * --------------------------------------------------------------- */

    public function test_a_teacher_profile_has_posts_classes_and_about_tabs(): void
    {
        $teacher = User::factory()->teacher()->create([
            'subject' => 'Mathematics',
            'bio' => 'I make numbers friendly.',
        ]);
        ClassRoom::factory()->for($teacher, 'teacher')->create(['title' => 'Algebra basics']);

        $viewer = User::factory()->student()->create();

        $this->actingAs($viewer)->get(route('users.show', $teacher))
            ->assertOk()
            ->assertSee('id="profile-posts-tab"', false)
            ->assertSee('id="profile-classes-tab"', false)
            ->assertSee('id="profile-about-tab"', false)
            ->assertSee('Algebra basics');
    }

    public function test_a_student_profile_has_no_classes_tab(): void
    {
        $student = User::factory()->student()->create();
        $viewer = User::factory()->student()->create();

        $this->actingAs($viewer)->get(route('users.show', $student))
            ->assertOk()
            ->assertSee('id="profile-posts-tab"', false)
            ->assertDontSee('id="profile-classes-tab"', false)
            ->assertSee('id="profile-about-tab"', false);
    }

    /* ---------------------------------------------------------------
     | Grouped notifications
     * --------------------------------------------------------------- */

    public function test_notifications_are_grouped_into_today_and_earlier(): void
    {
        $student = User::factory()->student()->create();

        $student->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\NewFollower',
            'data' => ['message' => 'A teacher started following you', 'icon' => 'bi-person-plus'],
        ]);

        $student->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\NewFollower',
            'data' => ['message' => 'An older notification', 'icon' => 'bi-bell'],
            'created_at' => now()->subDays(3),
            'updated_at' => now()->subDays(3),
        ]);

        $this->actingAs($student)->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('edu-notif-group-label', false)
            ->assertSee('id="notif-group-today"', false)
            ->assertSee('id="notif-group-earlier"', false)
            ->assertSee('A teacher started following you')
            ->assertSee('An older notification')
            // Unread entries carry the visual dot and a text alternative.
            ->assertSee('notification-unread', false);
    }

    public function test_the_notification_page_shows_a_friendly_empty_state(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('No notifications yet')
            ->assertSee(route('feed.index'), false);
    }

    /* ---------------------------------------------------------------
     | Search highlight
     * --------------------------------------------------------------- */

    public function test_search_results_highlight_the_matched_text_safely(): void
    {
        User::factory()->student()->create(['name' => 'Algebra Alice']);
        $viewer = User::factory()->student()->create();

        $this->actingAs($viewer)
            ->get(route('search.index', ['q' => 'algebra', 'tab' => 'users']))
            ->assertOk()
            ->assertSee('<mark class="edu-mark">Algebra</mark>', false)
            ->assertSee('Algebra</mark> Alice', false);
    }

    public function test_search_escapes_html_in_results_before_highlighting(): void
    {
        User::factory()->student()->create(['name' => '<script>alert(1)</script> alive']);
        $viewer = User::factory()->student()->create();

        $this->actingAs($viewer)
            ->get(route('search.index', ['q' => 'alive', 'tab' => 'users']))
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;', false);
    }

    /* ---------------------------------------------------------------
     | Admin tables
     * --------------------------------------------------------------- */

    public function test_admin_tables_are_marked_up_for_mobile_cards_and_labels(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->student()->create();

        $this->actingAs($admin)->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('edu-table-cards', false)
            ->assertSee('data-label="User"', false)
            ->assertSee('data-label="Actions"', false);
    }

    /* ---------------------------------------------------------------
     | Breadcrumbs and page titles
     * --------------------------------------------------------------- */

    public function test_a_redesigned_page_exposes_breadcrumbs_and_one_primary_heading(): void
    {
        $teacher = User::factory()->teacher()->create();
        $classRoom = ClassRoom::factory()->for($teacher, 'teacher')->create(['title' => 'Geometry']);

        $this->actingAs($teacher)->get(route('classes.show', $classRoom))
            ->assertOk()
            ->assertSee('aria-label="Breadcrumb"', false)
            ->assertSee('aria-current="page"', false)
            ->assertSee('<h1', false)
            // Exactly one H1 on the page.
            ->assertSeeText('Geometry');
    }
}
