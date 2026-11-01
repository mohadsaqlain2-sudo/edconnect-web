<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Phase 9: the regressions found by the final audit.
 *
 * Each test pins one finding so it cannot come back:
 *  - polymorphic reports and uploaded files are cleaned up on every deletion
 *    path (author, administrator, moderation queue, account deletion),
 *  - the moderation queue and the public profile do not run N+1 queries,
 *  - class search treats LIKE wildcards as literal text,
 *  - whitespace-only posts and comments are rejected,
 *  - registration actually sends the e-mail verification link,
 *  - the live-room help text matches the microphone defaults.
 */
class PhaseNineTest extends TestCase
{
    use RefreshDatabase;

    /* -----------------------------------------------------------------
     | Deletion cleanup (FR-8, FR-10, FR-28, FR-33, FR-34)
     * ----------------------------------------------------------------- */

    public function test_deleting_a_post_removes_its_reports_its_comment_reports_and_its_image(): void
    {
        Storage::fake('public');

        $author = User::factory()->teacher()->create();
        $post = Post::factory()->create([
            'user_id' => $author->id,
            'image' => 'posts/audit-image.jpg',
        ]);
        Storage::disk('public')->put('posts/audit-image.jpg', 'image-bytes');

        $comment = Comment::factory()->create(['post_id' => $post->id]);

        Report::factory()->create([
            'reportable_type' => Post::class,
            'reportable_id' => $post->id,
        ]);
        Report::factory()->create([
            'reportable_type' => Comment::class,
            'reportable_id' => $comment->id,
        ]);

        $post->delete();

        // The post and its comments disappear through the foreign keys.
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);

        // Reports are polymorphic, so they only disappear because of the hook.
        $this->assertDatabaseCount('reports', 0);
        Storage::disk('public')->assertMissing('posts/audit-image.jpg');
    }

    public function test_deleting_a_comment_removes_the_reports_about_it(): void
    {
        $post = Post::factory()->create();
        $comment = Comment::factory()->create(['post_id' => $post->id]);
        $otherComment = Comment::factory()->create(['post_id' => $post->id]);

        Report::factory()->create([
            'reportable_type' => Comment::class,
            'reportable_id' => $comment->id,
        ]);
        Report::factory()->create([
            'reportable_type' => Comment::class,
            'reportable_id' => $otherComment->id,
        ]);

        $comment->delete();

        $this->assertDatabaseMissing('reports', [
            'reportable_type' => Comment::class,
            'reportable_id' => $comment->id,
        ]);

        // A report about another comment is untouched.
        $this->assertDatabaseHas('reports', [
            'reportable_type' => Comment::class,
            'reportable_id' => $otherComment->id,
        ]);
    }

    public function test_deleting_a_user_cleans_reports_about_their_content_and_the_uploaded_files(): void
    {
        Storage::fake('public');

        $author = User::factory()->teacher()->create(['photo' => 'profiles/author.webp']);
        Storage::disk('public')->put('profiles/author.webp', 'photo');

        $ownPost = Post::factory()->create([
            'user_id' => $author->id,
            'image' => 'posts/author-post.jpg',
        ]);
        Storage::disk('public')->put('posts/author-post.jpg', 'image');

        $ownComment = Comment::factory()->create([
            'user_id' => $author->id,
            'post_id' => $ownPost->id,
        ]);

        // A comment by somebody else on the author's post: its report is
        // orphaned by the post cascade, not by the comment cascade.
        $foreignComment = Comment::factory()->create(['post_id' => $ownPost->id]);

        Report::factory()->create([
            'reportable_type' => Post::class,
            'reportable_id' => $ownPost->id,
        ]);
        Report::factory()->create([
            'reportable_type' => Comment::class,
            'reportable_id' => $ownComment->id,
        ]);
        Report::factory()->create([
            'reportable_type' => Comment::class,
            'reportable_id' => $foreignComment->id,
        ]);

        $author->delete();

        $this->assertDatabaseCount('posts', 0);
        $this->assertDatabaseCount('comments', 0);
        $this->assertDatabaseCount('reports', 0);

        Storage::disk('public')->assertMissing('profiles/author.webp');
        Storage::disk('public')->assertMissing('posts/author-post.jpg');
    }

    /* -----------------------------------------------------------------
     | Query budgets (SRS 5.1: N+1)
     * ----------------------------------------------------------------- */

    public function test_the_admin_report_queue_does_not_run_one_query_per_report(): void
    {
        $admin = User::factory()->admin()->create();
        $reporter = User::factory()->student()->create();
        $author = User::factory()->teacher()->create();

        $posts = Post::factory()->count(20)->create(['user_id' => $author->id]);

        foreach ($posts as $post) {
            Report::factory()->create([
                'reporter_id' => $reporter->id,
                'reportable_type' => Post::class,
                'reportable_id' => $post->id,
            ]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->actingAs($admin)->get(route('admin.reports.index'))->assertOk();

        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Reporter + reportable per type + author per type are eager loaded, so
        // the count stays flat instead of growing with the 20 rows.
        $this->assertLessThanOrEqual(
            15,
            $queries,
            "The moderation queue ran {$queries} queries for 20 reports; eager loading is missing."
        );
    }

    public function test_the_public_profile_loads_class_cards_without_n_plus_one(): void
    {
        $teacher = User::factory()->teacher()->create();
        $viewer = User::factory()->student()->create();

        ClassRoom::factory()->count(6)->scheduled()->create([
            'teacher_id' => $teacher->id,
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->actingAs($viewer)->get(route('users.show', $teacher))->assertOk();

        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        // The cards reuse the eager loaded teacher and students_count instead
        // of running 12 extra queries for six classes.
        $this->assertLessThanOrEqual(
            15,
            $queries,
            "The profile page ran {$queries} queries for 6 class cards; eager loading is missing."
        );
    }

    /* -----------------------------------------------------------------
     | Search (FR-19)
     * ----------------------------------------------------------------- */

    public function test_class_search_treats_like_wildcards_as_literal_text(): void
    {
        $student = User::factory()->student()->create();
        $teacher = User::factory()->teacher()->create(['name' => 'Maria Rossi']);

        ClassRoom::factory()->scheduled()->create([
            'teacher_id' => $teacher->id,
            'title' => 'Algebra basics',
            'subject' => 'Mathematics',
        ]);

        ClassRoom::factory()->scheduled()->create([
            'teacher_id' => $teacher->id,
            'title' => '100% Physics',
            'subject' => 'Physics',
        ]);

        // "%" must match the literal percent sign, not every class.
        $percent = $this->actingAs($student)
            ->get(route('classes.discover', ['q' => '%']))
            ->assertOk();

        $this->assertSame(1, $percent->viewData('classes')->total());
        $this->assertSame('100% Physics', $percent->viewData('classes')->first()->title);

        // "_" matches a single character in SQL; no title contains one.
        $underscore = $this->actingAs($student)
            ->get(route('classes.discover', ['q' => '_']))
            ->assertOk();

        $this->assertSame(0, $underscore->viewData('classes')->total());
    }

    /* -----------------------------------------------------------------
     | Validation (FR-7, FR-8, FR-10)
     * ----------------------------------------------------------------- */

    public function test_whitespace_only_posts_and_comments_are_rejected(): void
    {
        $student = User::factory()->student()->create();
        $author = User::factory()->teacher()->create();
        $post = Post::factory()->create(['user_id' => $author->id]);

        $this->actingAs($student)
            ->post(route('posts.store'), ['body' => "   \n\t  "])
            ->assertSessionHasErrors('body');

        $this->assertDatabaseCount('posts', 1);

        $this->actingAs($student)
            ->post(route('comments.store', $post), ['body' => "   \n "])
            ->assertSessionHasErrors('body');

        $this->assertDatabaseCount('comments', 0);

        // Editing a post down to spaces is refused too.
        $this->actingAs($author)
            ->patch(route('posts.update', $post), ['body' => '    '])
            ->assertSessionHasErrors('body');

        $this->assertNotSame('', trim((string) $post->fresh()->body));
    }

    public function test_the_body_is_trimmed_before_it_is_stored(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)
            ->post(route('posts.store'), ['body' => '  Hello EduConnect  '])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('posts', [
            'user_id' => $student->id,
            'body' => 'Hello EduConnect',
        ]);
    }

    /* -----------------------------------------------------------------
     | E-mail verification (FR-1)
     * ----------------------------------------------------------------- */

    public function test_registration_sends_the_email_verification_notification(): void
    {
        Notification::fake();

        $this->post('/register', [
            'name' => 'Verify Me',
            'email' => 'verify.me@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'student',
        ])->assertRedirect('/student/dashboard');

        $user = User::query()->where('email', 'verify.me@example.com')->firstOrFail();

        // The interface is what makes Laravel send the signed link.
        $this->assertInstanceOf(MustVerifyEmail::class, $user);
        $this->assertFalse($user->hasVerifiedEmail());

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    /* -----------------------------------------------------------------
     | Live room copy (FR-20, FR-22)
     * ----------------------------------------------------------------- */

    public function test_the_live_room_microphone_help_matches_the_defaults(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();

        $classRoom = ClassRoom::factory()->live()->create([
            'teacher_id' => $teacher->id,
        ]);
        $classRoom->students()->attach($student->id);

        // The moderator starts unmuted (jitsi.js sets startWithAudioMuted=false).
        $this->actingAs($teacher)
            ->get(route('classes.room', $classRoom))
            ->assertOk()
            ->assertSee('Your microphone starts on', false)
            ->assertDontSee('Your microphone starts muted', false);

        // Students start muted.
        $this->actingAs($student)
            ->get(route('classes.room', $classRoom))
            ->assertOk()
            ->assertSee('Your microphone starts muted', false)
            ->assertDontSee('Your microphone starts on', false);
    }
}
