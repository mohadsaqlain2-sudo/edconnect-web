<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\Post;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 3: the three role dashboards (FR-2, FR-19, FR-20, FR-21, FR-22, FR-31).
 */
class DashboardTest extends TestCase
{
    use RefreshDatabase;

    /* ---------------------------------------------------------------
     | Role isolation
     * --------------------------------------------------------------- */

    /**
     * Every role only reaches its own dashboard (FR-2, FR-5).
     */
    public function test_each_role_is_locked_out_of_the_other_dashboards(): void
    {
        // A teacher cannot open the admin or the student dashboard.
        $teacher = User::factory()->teacher()->create();
        $this->actingAs($teacher)->get('/admin/dashboard')->assertForbidden();
        $this->actingAs($teacher)->get('/student/dashboard')->assertForbidden();

        // A student cannot open the admin or the teacher dashboard.
        $student = User::factory()->student()->create();
        $this->actingAs($student)->get('/admin/dashboard')->assertForbidden();
        $this->actingAs($student)->get('/teacher/dashboard')->assertForbidden();

        // An admin cannot open the teacher or the student dashboard.
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get('/teacher/dashboard')->assertForbidden();
        $this->actingAs($admin)->get('/student/dashboard')->assertForbidden();
    }

    /* ---------------------------------------------------------------
     | Admin dashboard
     * --------------------------------------------------------------- */

    /**
     * The admin cards show the real platform totals (FR-7).
     */
    public function test_the_admin_dashboard_shows_the_platform_totals(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->count(2)->teacher()->create();
        $students = User::factory()->count(3)->student()->create();

        Post::factory()->create(['user_id' => $students->first()->id]);
        ClassRoom::factory()->create(['teacher_id' => User::where('role', User::ROLE_TEACHER)->first()->id]);

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertOk();
        $response->assertViewHas('stats', fn (array $stats) => $stats === [
            'users' => 6,       // 1 admin + 2 teachers + 3 students
            'teachers' => 2,
            'students' => 3,
            'posts' => 1,
            'classes' => 1,
        ]);
    }

    /**
     * The chart covers exactly 12 months, keeps the months in order and fills
     * months without registrations with 0 (FR-31).
     */
    public function test_the_admin_chart_counts_new_users_per_month(): void
    {
        // Freeze time so the 12 month window is predictable.
        $this->travelTo(Carbon::parse('2026-06-15 12:00:00'));

        $admin = User::factory()->admin()->create();
        User::factory()->count(2)->create();          // 3 registrations in June 2026

        $this->travelTo(Carbon::parse('2026-04-10 09:00:00'));
        User::factory()->count(2)->create();          // 2 registrations in April 2026

        $this->travelTo(Carbon::parse('2025-01-01 09:00:00'));
        User::factory()->create();                    // outside the window -> ignored

        $this->travelTo(Carbon::parse('2026-06-15 12:00:00'));

        $response = $this->actingAs($admin)->get('/admin/dashboard');
        $response->assertOk();

        $chart = $response->viewData('chart');

        // 12 months, oldest first: July 2025 ... June 2026.
        $this->assertCount(12, $chart['labels']);
        $this->assertSame('Jul 2025', $chart['labels'][0]);
        $this->assertSame('Jun 2026', $chart['labels'][11]);

        // April 2026 is index 9, June 2026 is index 11.
        $this->assertSame(2, $chart['values'][9]);
        $this->assertSame(3, $chart['values'][11]);

        // Months without new users are 0, not missing.
        $this->assertSame(0, $chart['values'][0]);
        $this->assertSame(0, $chart['values'][10]);

        // The user from January 2025 is not part of the last 12 months.
        $this->assertSame(5, $chart['total']);

        // The canvas that app.js turns into a chart is on the page.
        $response->assertSee('data-chart', false);
    }

    /* ---------------------------------------------------------------
     | Teacher dashboard
     * --------------------------------------------------------------- */

    /**
     * The teacher cards only count the teacher's own content (FR-22).
     */
    public function test_the_teacher_dashboard_only_counts_my_own_content(): void
    {
        $teacher = User::factory()->teacher()->create();
        $otherTeacher = User::factory()->teacher()->create();

        // Two of my classes: one still ahead, one already finished.
        $upcoming = $this->makeClass($teacher, 'scheduled');
        $finished = $this->makeClass($teacher, 'completed');

        // A class that belongs to somebody else and must stay invisible.
        $this->makeClass($otherTeacher, 'scheduled', 'Secret other class');

        // Two students, one of them enrolled in both of my classes.
        [$sina, $paul] = User::factory()->count(2)->student()->create()->all();
        $upcoming->students()->attach([$sina->id, $paul->id]);
        $finished->students()->attach($sina->id);

        // Both students follow me.
        $sina->follow($teacher);
        $paul->follow($teacher);

        $response = $this->actingAs($teacher)->get('/teacher/dashboard');

        $response->assertOk();
        $response->assertViewHas('stats', fn (array $stats) => $stats === [
            'classes' => 2,
            'upcoming' => 1,
            'students' => 2,   // Sina counts once, even though she is in two classes
            'followers' => 2,
        ]);

        // The table only lists my classes.
        $classes = $response->viewData('classes');
        $this->assertSame(2, $classes->total());
        $response->assertDontSee('Secret other class', false);
        $this->assertStringNotContainsString('Secret other class', $response->getContent());

        // The activity stream reports the new followers (FR-25).
        $response->assertSee('started following you');
    }

    /* ---------------------------------------------------------------
     | Student dashboard
     * --------------------------------------------------------------- */

    /**
     * The student dashboard lists the enrolled upcoming classes, the correct
     * counters and only teachers that are not followed yet (FR-19, FR-20, FR-21).
     */
    public function test_the_student_dashboard_shows_upcoming_classes_and_teacher_suggestions(): void
    {
        $student = User::factory()->student()->create();

        $followed = User::factory()->teacher()->create();
        $suggested = User::factory()->teacher()->create();
        $alsoSuggested = User::factory()->teacher()->create();
        $student->follow($followed);

        // I am enrolled in an upcoming and in a finished class of $followed.
        $upcoming = $this->makeClass($followed, 'scheduled');
        $finished = $this->makeClass($followed, 'completed');
        $upcoming->students()->attach($student->id);
        $finished->students()->attach($student->id);

        // An upcoming class I am NOT enrolled in: it must not be listed.
        $notMine = $this->makeClass($suggested, 'scheduled');

        $response = $this->actingAs($student)->get('/student/dashboard');

        $response->assertOk();
        $response->assertViewHas('stats', fn (array $stats) => $stats === [
            'classes' => 2,
            'upcoming' => 1,
            'following' => 1,
        ]);

        // Only the upcoming class I am enrolled in is listed.
        $upcomingClasses = $response->viewData('upcomingClasses');
        $this->assertSame([$upcoming->id], $upcomingClasses->pluck('id')->all());
        $this->assertNotContains($finished->id, $upcomingClasses->pluck('id')->all());
        $this->assertNotContains($notMine->id, $upcomingClasses->pluck('id')->all());

        // Teachers I do not follow yet are recommended, the followed one is not.
        $recommended = $response->viewData('recommendedTeachers')->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$suggested->id, $alsoSuggested->id], $recommended);
        $this->assertNotContains($followed->id, $recommended);

        // The Join button is on the page (it becomes a real link in Phase 5).
        $response->assertSee('Join');
    }

    /* ---------------------------------------------------------------
     | Follow / unfollow (FR-11)
     * --------------------------------------------------------------- */

    /**
     * A student can follow and unfollow a teacher.
     */
    public function test_a_student_can_follow_and_unfollow_a_teacher(): void
    {
        $student = User::factory()->student()->create();
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($student)
            ->post(route('follows.store', $teacher))
            ->assertRedirect();

        $this->assertDatabaseHas('follows', [
            'follower_id' => $student->id,
            'following_id' => $teacher->id,
        ]);

        // Following twice does not create a second row.
        $this->actingAs($student)->post(route('follows.store', $teacher))->assertRedirect();
        $this->assertDatabaseCount('follows', 1);

        $this->actingAs($student)
            ->delete(route('follows.destroy', $teacher))
            ->assertRedirect();

        $this->assertDatabaseCount('follows', 0);
    }

    /**
     * Members can follow each other, but never themselves and never an admin.
     */
    public function test_a_student_can_follow_another_student_but_not_themselves_or_an_admin(): void
    {
        $student = User::factory()->student()->create();
        $otherStudent = User::factory()->student()->create();
        $admin = User::factory()->admin()->create();

        // FR-11: following is not limited to teachers.
        $this->actingAs($student)
            ->post(route('follows.store', $otherStudent))
            ->assertRedirect();

        // Following yourself is forbidden.
        $this->actingAs($student)
            ->post(route('follows.store', $student))
            ->assertForbidden();

        // Admin accounts are not followable -> treated as not found.
        $this->actingAs($student)
            ->post(route('follows.store', $admin))
            ->assertNotFound();

        $this->assertDatabaseCount('follows', 1);
    }

    /**
     * Teachers can use the follow routes, admins cannot.
     */
    public function test_teachers_can_follow_but_admins_cannot(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        $admin = User::factory()->admin()->create();

        // A teacher may follow a student (FR-11: users follow users).
        $this->actingAs($teacher)
            ->post(route('follows.store', $student))
            ->assertRedirect();

        // The admin area is not part of the social graph.
        $this->actingAs($admin)
            ->post(route('follows.store', $teacher))
            ->assertForbidden();
    }

    /* ---------------------------------------------------------------
     | Error pages
     * --------------------------------------------------------------- */

    /**
     * The custom Bootstrap error pages are in place.
     */
    public function test_the_custom_error_pages_are_rendered(): void
    {
        // A guest asking for a page that does not exist.
        $this->get('/this-page-does-not-exist')
            ->assertNotFound()
            ->assertSee('Page not found');

        // A student opening an admin page.
        $this->actingAs(User::factory()->student()->create())
            ->get('/admin/dashboard')
            ->assertForbidden()
            ->assertSee('You are not allowed to open this page');

        // The 500 page is rendered directly (the debug page replaces it while
        // APP_DEBUG is on, so it cannot be triggered through a request).
        $this->view('errors.500')->assertSee('500');
    }

    /* ---------------------------------------------------------------
     | Helpers
     * --------------------------------------------------------------- */

    /**
     * Build a class for a teacher with a predictable status and title.
     */
    private function makeClass(User $teacher, string $status, ?string $title = null): ClassRoom
    {
        $factory = match ($status) {
            ClassRoom::STATUS_LIVE => ClassRoom::factory()->live(),
            ClassRoom::STATUS_COMPLETED => ClassRoom::factory()->completed(),
            default => ClassRoom::factory()->scheduled(),
        };

        $attributes = ['teacher_id' => $teacher->id];

        if ($title) {
            $attributes['title'] = $title;
        }

        return $factory->create($attributes);
    }
}
