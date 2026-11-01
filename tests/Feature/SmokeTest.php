<?php

namespace Tests\Feature;

use App\Models\ClassEnrollment;
use App\Models\ClassRoom;
use App\Models\Hashtag;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 9 smoke test: after seeding the demo data, every important page must
 * render for the right role, and the reporting/moderation flow must work
 * end-to-end. This is the script used in the final walkthrough.
 */
class SmokeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $teacher;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        // The same seed a fresh install runs: php artisan migrate --seed.
        $this->seed(DatabaseSeeder::class);

        $this->admin = User::where('role', User::ROLE_ADMIN)->firstOrFail();
        $this->teacher = User::where('role', User::ROLE_TEACHER)->firstOrFail();
        $this->student = User::where('role', User::ROLE_STUDENT)->firstOrFail();
    }

    public function test_guest_pages_render(): void
    {
        $this->get('/')->assertOk()->assertSee('EduConnect');
        $this->get(route('login'))->assertOk();
        $this->get(route('register'))->assertOk();

        // Guests are pushed to the login page, never to a dashboard.
        $this->get(route('feed.index'))->assertRedirect(route('login'));
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_admin_can_open_every_admin_page(): void
    {
        $report = Report::query()->firstOrFail();

        $pages = [
            route('admin.dashboard'),
            route('admin.users.index'),
            route('admin.posts.index'),
            route('admin.classes.index'),
            route('admin.reports.index'),
            route('admin.reports.show', $report),
            route('feed.index'),
            route('search.index'),
            route('notifications.index'),
            route('profile.edit'),
            route('users.show', $this->admin),
            route('classes.discover'),
        ];

        foreach ($pages as $url) {
            $this->actingAs($this->admin)->get($url)->assertOk();
        }

        // A wrong role gets a 403, not the admin data.
        $this->actingAs($this->student)->get(route('admin.reports.index'))->assertForbidden();
        $this->actingAs($this->teacher)->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_teacher_can_open_their_pages(): void
    {
        $classRoom = ClassRoom::where('teacher_id', $this->teacher->id)->firstOrFail();
        $post = Post::query()->visible()->firstOrFail();
        $hashtag = Hashtag::query()->firstOrFail();

        $pages = [
            route('teacher.dashboard'),
            route('classes.index'),
            route('classes.create'),
            route('classes.show', $classRoom),
            route('classes.edit', $classRoom),
            route('classes.students', $classRoom),
            route('classes.room', $classRoom),
            route('classes.discover'),
            route('feed.index'),
            route('posts.show', $post),
            route('hashtags.show', $hashtag),
            route('search.index', ['q' => 'math', 'tab' => 'hashtags']),
            route('notifications.index'),
            route('profile.edit'),
            route('users.show', $this->teacher),
            route('users.followers', $this->teacher),
            route('users.following', $this->teacher),
        ];

        foreach ($pages as $url) {
            $this->actingAs($this->teacher)->get($url)->assertOk();
        }
    }

    public function test_student_can_open_their_pages_and_join_a_live_class(): void
    {
        $classRoom = ClassRoom::where('teacher_id', $this->teacher->id)->firstOrFail();
        $this->student->enrolledClasses()->syncWithoutDetaching([$classRoom->id]);

        // A student may only enter the room while the class is live.
        $classRoom->update(['status' => ClassRoom::STATUS_LIVE]);
        $this->assertTrue($classRoom->fresh()->isLive());

        $post = Post::query()->visible()->firstOrFail();

        $pages = [
            route('student.dashboard'),
            route('student.classes'),
            route('classes.discover'),
            route('classes.show', $classRoom),
            route('classes.room', $classRoom),
            route('feed.index'),
            route('posts.show', $post),
            route('search.index', ['q' => 'teacher']),
            route('notifications.index'),
            route('profile.edit'),
            route('users.show', $this->teacher),
        ];

        foreach ($pages as $url) {
            $this->actingAs($this->student)->get($url)->assertOk();
        }

        // Teacher-only pages stay closed to students.
        $this->actingAs($this->student)->get(route('classes.create'))->assertForbidden();
        $this->actingAs($this->student)->get(route('teacher.dashboard'))->assertForbidden();
    }

    public function test_the_report_to_moderation_flow_works_end_to_end(): void
    {
        // The demo seeder files random reports, one of them by the first
        // student. Pick a visible post this student has not reported yet, so
        // the test does not depend on the seed's random choice.
        $post = Post::query()
            ->visible()
            ->where('user_id', '!=', $this->student->id)
            ->whereDoesntHave('reports', fn ($query) => $query->where('reporter_id', $this->student->id))
            ->firstOrFail();

        // 1. Student reports a post.
        $this->actingAs($this->student)
            ->post(route('reports.posts.store', $post), [
                'reason' => 'spam',
                'details' => 'Smoke test report.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $report = Report::query()
            ->where('reportable_id', $post->id)
            ->where('reporter_id', $this->student->id)
            ->firstOrFail();
        $this->assertSame(Report::STATUS_PENDING, $report->status);

        // 2. The admin sees it in the queue and on the detail page.
        $this->actingAs($this->admin)
            ->get(route('admin.reports.index', ['status' => 'pending']))
            ->assertOk()
            ->assertSee('Spam');

        $this->actingAs($this->admin)
            ->get(route('admin.reports.show', $report))
            ->assertOk()
            ->assertSee('Smoke test report.');

        // 3. The admin hides the post and the report becomes reviewed.
        $this->actingAs($this->admin)
            ->patch(route('admin.reports.hide', $report))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue($post->fresh()->is_hidden);
        $this->assertSame(Report::STATUS_REVIEWED, $report->fresh()->status);

        // 4. The post is gone from the feed for everybody else.
        $this->actingAs($this->teacher)
            ->get(route('feed.index'))
            ->assertOk()
            ->assertDontSee($post->body);
    }

    public function test_every_list_page_has_an_empty_state(): void
    {
        // Wipe the social content but keep the people, then every list that can
        // become empty must still answer 200 and say something friendly.
        Report::query()->delete();
        Post::query()->delete();
        ClassEnrollment::query()->delete();
        ClassRoom::query()->delete();

        $this->actingAs($this->admin)
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee('No reports match these filters.');

        $this->actingAs($this->admin)
            ->get(route('admin.posts.index'))
            ->assertOk()
            ->assertSee('No posts match these filters.');

        $this->actingAs($this->admin)
            ->get(route('admin.classes.index'))
            ->assertOk()
            ->assertSee('No classes match these filters.');

        $this->actingAs($this->student)
            ->get(route('student.classes'))
            ->assertOk();

        $this->actingAs($this->student)
            ->get(route('classes.discover'))
            ->assertOk()
            ->assertSee('No class matches your search');
    }
}
