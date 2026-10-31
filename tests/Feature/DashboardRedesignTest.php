<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 3B: the premium dashboard redesign.
 *
 * These tests protect the new UI contracts (theme toggle, chart endpoints,
 * dashboard panels and the per-role hero sections) without duplicating the
 * data contracts that DashboardTest already covers.
 */
class DashboardRedesignTest extends TestCase
{
    use RefreshDatabase;

    /* ---------------------------------------------------------------
     | Shared layout and design system
     * --------------------------------------------------------------- */

    public function test_the_layout_ships_the_theme_toggle_and_the_design_tokens(): void
    {
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($teacher)->get(route('teacher.dashboard'))
            ->assertOk()
            // Theme is restored before the first paint and persisted by theme.js.
            ->assertSee('data-theme-toggle', false)
            ->assertSee('educonnect.theme', false)
            ->assertSee('prefers-color-scheme', false)
            // Inter and the sticky shell are part of the redesign.
            ->assertSee('fonts.googleapis.com', false)
            ->assertSee('edu-navbar', false)
            ->assertSee('edu-sidebar', false)
            // Every dashboard uses the shared page wrapper.
            ->assertSee('edu-dashboard', false)
            // The desktop sidebar toggle and the footer copy stay intact.
            ->assertSee('data-sidebar-toggle', false)
            ->assertSee('Learn together, anywhere.');
    }

    /* ---------------------------------------------------------------
     | Chart JSON endpoints and role isolation
     * --------------------------------------------------------------- */

    public function test_the_dashboard_chart_endpoints_are_role_protected(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();

        // Admin series: five keys, including the 12-month compatible shape.
        $this->actingAs($admin)
            ->getJson(route('admin.dashboard.charts', ['range' => '7d']))
            ->assertOk()
            ->assertJsonPath('range', '7d')
            ->assertJsonStructure([
                'range',
                'newUsers' => ['labels', 'values', 'total'],
                'posts' => ['labels', 'values', 'total'],
                'roles' => ['labels', 'values', 'total'],
                'classes' => ['labels', 'values', 'total'],
            ]);

        // A teacher cannot read the admin series.
        $this->actingAs($teacher)
            ->getJson(route('admin.dashboard.charts'))
            ->assertForbidden();

        // Teacher series.
        $this->actingAs($teacher)
            ->getJson(route('teacher.dashboard.charts'))
            ->assertOk()
            ->assertJsonStructure([
                'range',
                'enrollments' => ['labels', 'values', 'total'],
                'followers' => ['labels', 'values', 'total'],
            ]);

        // A student cannot read the teacher series.
        $this->actingAs($student)
            ->getJson(route('teacher.dashboard.charts'))
            ->assertForbidden();

        // Student series: seven daily buckets for the 7-day range.
        $response = $this->actingAs($student)
            ->getJson(route('student.dashboard.charts', ['range' => '7d']))
            ->assertOk()
            ->assertJsonPath('range', '7d')
            ->assertJsonStructure(['range', 'activity' => ['labels', 'values', 'total']]);

        $this->assertCount(7, $response->json('activity.labels'));

        // The student cannot read the admin series either.
        $this->actingAs($student)
            ->getJson(route('admin.dashboard.charts'))
            ->assertForbidden();
    }

    /* ---------------------------------------------------------------
     | Admin dashboard panels
     * --------------------------------------------------------------- */

    public function test_the_admin_dashboard_renders_the_new_charts_panels_and_moderation_shortcuts(): void
    {
        $admin = User::factory()->admin()->create();
        $reporter = User::factory()->student()->create(['name' => 'Report Author']);
        $post = Post::factory()->create([
            'user_id' => $reporter->id,
            'body' => 'A reported post that needs a moderator.',
        ]);
        Report::factory()->create([
            'reporter_id' => $reporter->id,
            'reportable_type' => Post::class,
            'reportable_id' => $post->id,
        ]);

        $teacher = User::factory()->teacher()->create();
        ClassRoom::factory()->live()->create([
            'teacher_id' => $teacher->id,
            'title' => 'Live Algebra Room',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk()
            ->assertSee('New users per month')
            ->assertSee('Users by role')
            ->assertSee('Posts per week')
            ->assertSee('Classes by status')
            ->assertSee('Pending reports')
            ->assertSee('Latest posts')
            ->assertSee("Today's live classes")
            ->assertSee('Recent users')
            ->assertSee('Live Algebra Room')
            // The four Chart.js canvases and the JSON endpoint are wired up.
            ->assertSee('data-chart-card', false)
            ->assertSee(route('admin.dashboard.charts'), false)
            // Quick moderation buttons appear for a pending report.
            ->assertSee(route('admin.reports.hide', Report::first()), false)
            ->assertSee(route('admin.reports.dismiss', Report::first()), false);

        // The contracted stats keep exactly five keys.
        $this->assertSame(
            ['users', 'teachers', 'students', 'posts', 'classes'],
            array_keys($response->viewData('stats'))
        );

        // Trend + sparkline data is available for every card.
        $this->assertSame(
            ['users', 'teachers', 'students', 'posts', 'classes'],
            array_keys($response->viewData('trends'))
        );
    }

    /* ---------------------------------------------------------------
     | Teacher dashboard hero, schedule and table
     * --------------------------------------------------------------- */

    public function test_the_teacher_dashboard_has_the_hero_schedule_and_enrollment_progress(): void
    {
        $teacher = User::factory()->teacher()->create(['name' => 'Tina Teacher']);
        $student = User::factory()->student()->create(['name' => 'Hero Student']);
        $class = ClassRoom::factory()->scheduled()->create([
            'teacher_id' => $teacher->id,
            'title' => 'Hero Algebra',
        ]);
        $class->students()->attach($student->id);

        $response = $this->actingAs($teacher)->get(route('teacher.dashboard'));

        $response->assertOk()
            ->assertSee('Next class')
            ->assertSee('Hero Algebra')
            // Live countdown and the seven-day strip.
            ->assertSee('data-countdown', false)
            ->assertSee('edu-schedule', false)
            ->assertSee('This week')
            // Charts, table tools, actions and the activity feed.
            ->assertSee('Enrollments per class')
            ->assertSee('Follower growth')
            ->assertSee('data-table-search', false)
            ->assertSee('Actions')
            ->assertSee('Recent activity')
            ->assertSee(route('classes.students', $class), false)
            ->assertSee(route('teacher.dashboard.charts'), false);

        $this->assertSame($class->id, $response->viewData('nextClass')->id);
        $this->assertCount(7, $response->viewData('schedule'));
        $this->assertSame(1, $response->viewData('maxEnrollment'));
    }

    /* ---------------------------------------------------------------
     | Student dashboard hero, recommendations and feed preview
     * --------------------------------------------------------------- */

    public function test_the_student_dashboard_has_the_hero_recommendations_chart_and_feed_preview(): void
    {
        $student = User::factory()->student()->create(['name' => 'Nina Student']);
        $followed = User::factory()->teacher()->create(['name' => 'Followed Teacher']);
        $suggested = User::factory()->teacher()->create(['name' => 'Suggested Teacher']);
        $student->follow($followed);

        $class = ClassRoom::factory()->scheduled()->create([
            'teacher_id' => $followed->id,
            'title' => 'Nina Next Class',
            'subject' => 'Mathematics',
        ]);
        $class->students()->attach($student->id);

        // Same subject as the enrolled class, so it is recommended even though
        // the teacher is not followed yet.
        ClassRoom::factory()->scheduled()->create([
            'teacher_id' => $suggested->id,
            'title' => 'Suggested Mathematics Class',
            'subject' => 'Mathematics',
        ]);

        Post::factory()->create([
            'user_id' => $followed->id,
            'body' => 'A post from a followed teacher.',
        ]);

        $response = $this->actingAs($student)->get(route('student.dashboard'));

        $response->assertOk()
            ->assertSee('Your next class')
            ->assertSee('Nina Next Class')
            ->assertSee('data-countdown', false)
            ->assertSee('Recommended teachers')
            ->assertSee('Suggested Teacher')
            // The existing AJAX follow form is reused.
            ->assertSee('data-follow-form', false)
            ->assertSee('Recommended for you')
            ->assertSee('Suggested Mathematics Class')
            ->assertSee('Learning activity')
            ->assertSee('From people you follow')
            ->assertSee('A post from a followed teacher.')
            ->assertSee('Open full feed')
            ->assertSee(route('student.dashboard.charts'), false);

        // Contracted collections keep their shape.
        $this->assertSame(
            ['classes', 'upcoming', 'following'],
            array_keys($response->viewData('stats'))
        );
        $this->assertSame([$class->id], $response->viewData('upcomingClasses')->pluck('id')->all());
        $this->assertSame(
            ['Suggested Teacher'],
            $response->viewData('recommendedTeachers')->pluck('name')->all()
        );
        $this->assertCount(1, $response->viewData('feedPreview'));
    }
}
