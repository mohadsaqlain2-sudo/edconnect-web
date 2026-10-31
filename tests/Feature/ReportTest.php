<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FR-32 ... FR-34: reporting and the moderation queue.
 */
class ReportTest extends TestCase
{
    use RefreshDatabase;

    /* -----------------------------------------------------------------
     | Reporting (FR-32)
     * ----------------------------------------------------------------- */

    public function test_a_guest_cannot_report_content(): void
    {
        $post = Post::factory()->create();

        $this->post(route('reports.posts.store', $post), ['reason' => 'spam'])
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('reports', 0);
    }

    public function test_a_user_can_report_somebody_elses_post(): void
    {
        $author = User::factory()->teacher()->create();
        $reporter = User::factory()->student()->create();
        $post = Post::factory()->create(['user_id' => $author->id]);

        $this->actingAs($reporter)
            ->post(route('reports.posts.store', $post), [
                'reason' => 'harassment',
                'details' => 'This post attacks a student.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('reports', [
            'reporter_id' => $reporter->id,
            'reportable_type' => Post::class,
            'reportable_id' => $post->id,
            'reason' => 'harassment',
            'details' => 'This post attacks a student.',
            'status' => Report::STATUS_PENDING,
        ]);
    }

    public function test_a_user_can_report_a_comment(): void
    {
        $reporter = User::factory()->student()->create();
        $comment = Comment::factory()->create();

        $this->actingAs($reporter)
            ->post(route('reports.comments.store', $comment), ['reason' => 'spam'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('reports', [
            'reporter_id' => $reporter->id,
            'reportable_type' => Comment::class,
            'reportable_id' => $comment->id,
            'status' => Report::STATUS_PENDING,
        ]);
    }

    public function test_a_user_cannot_report_their_own_content(): void
    {
        $author = User::factory()->student()->create();
        $post = Post::factory()->create(['user_id' => $author->id]);
        $comment = Comment::factory()->create(['user_id' => $author->id]);

        $this->actingAs($author)
            ->post(route('reports.posts.store', $post), ['reason' => 'spam'])
            ->assertForbidden();

        $this->actingAs($author)
            ->post(route('reports.comments.store', $comment), ['reason' => 'spam'])
            ->assertForbidden();

        $this->assertDatabaseCount('reports', 0);
    }

    public function test_the_same_user_cannot_report_the_same_item_twice(): void
    {
        $reporter = User::factory()->student()->create();
        $post = Post::factory()->create();

        $this->actingAs($reporter)
            ->post(route('reports.posts.store', $post), ['reason' => 'spam'])
            ->assertSessionHas('success');

        // The second attempt is refused with a friendly flash message.
        $this->actingAs($reporter)
            ->post(route('reports.posts.store', $post), ['reason' => 'harassment'])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('reports', 1);
    }

    public function test_the_reason_is_required_and_details_are_limited(): void
    {
        $reporter = User::factory()->student()->create();
        $post = Post::factory()->create();

        $this->actingAs($reporter)
            ->post(route('reports.posts.store', $post), ['reason' => '', 'details' => str_repeat('a', 501)])
            ->assertSessionHasErrors(['reason', 'details']);

        $this->actingAs($reporter)
            ->post(route('reports.posts.store', $post), ['reason' => 'not-a-real-reason'])
            ->assertSessionHasErrors('reason');

        $this->assertDatabaseCount('reports', 0);
    }

    public function test_hidden_content_cannot_be_reported(): void
    {
        $reporter = User::factory()->student()->create();
        $post = Post::factory()->hidden()->create();

        $this->actingAs($reporter)
            ->post(route('reports.posts.store', $post), ['reason' => 'spam'])
            ->assertNotFound();
    }

    /* -----------------------------------------------------------------
     | The admin queue (FR-33, FR-34)
     * ----------------------------------------------------------------- */

    public function test_only_admins_can_open_the_reports_queue(): void
    {
        $report = Report::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee($report->content_preview);

        $this->actingAs(User::factory()->teacher()->create())
            ->get(route('admin.reports.index'))
            ->assertForbidden();

        $this->actingAs(User::factory()->student()->create())
            ->get(route('admin.reports.show', $report))
            ->assertForbidden();
    }

    public function test_the_queue_can_be_filtered_by_status_and_type(): void
    {
        $admin = User::factory()->admin()->create();

        $pending = Report::factory()->forPost()->create();
        Report::factory()->forComment()->reviewed()->create();

        $response = $this->actingAs($admin)
            ->get(route('admin.reports.index', ['status' => 'pending', 'type' => 'post']))
            ->assertOk();

        $reports = $response->viewData('reports');

        $this->assertSame(1, $reports->total());
        $this->assertSame($pending->id, $reports->first()->id);
    }

    public function test_hiding_a_post_marks_the_report_reviewed_and_removes_it_from_the_feed(): void
    {
        $admin = User::factory()->admin()->create();
        $author = User::factory()->teacher()->create();
        $follower = User::factory()->student()->create();
        $follower->follow($author);

        $post = Post::factory()->create(['user_id' => $author->id, 'body' => 'Very rude post body']);
        $report = Report::factory()->create([
            'reportable_type' => Post::class,
            'reportable_id' => $post->id,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.reports.hide', $report))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue($post->fresh()->is_hidden);
        $this->assertSame(Report::STATUS_REVIEWED, $report->fresh()->status);

        // The follower no longer sees it in the feed.
        $this->actingAs($follower)
            ->get(route('feed.index'))
            ->assertOk()
            ->assertDontSee('Very rude post body');

        // Unhide works too.
        $this->actingAs($admin)->patch(route('admin.reports.unhide', $report))->assertRedirect();
        $this->assertFalse($post->fresh()->is_hidden);
    }

    public function test_hiding_a_comment_removes_it_from_the_post_page(): void
    {
        $admin = User::factory()->admin()->create();

        $post = Post::factory()->create();
        $comment = Comment::factory()->create([
            'post_id' => $post->id,
            'body' => 'A comment that must disappear',
        ]);

        $report = Report::factory()->create([
            'reportable_type' => Comment::class,
            'reportable_id' => $comment->id,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.reports.hide', $report))
            ->assertRedirect();

        $this->assertTrue($comment->fresh()->is_hidden);

        $this->actingAs($admin)
            ->get(route('posts.show', $post))
            ->assertOk()
            ->assertDontSee('A comment that must disappear');
    }

    public function test_blocking_the_author_marks_the_report_reviewed_and_logs_them_out(): void
    {
        $admin = User::factory()->admin()->create();
        $author = User::factory()->student()->create();
        $post = Post::factory()->create(['user_id' => $author->id]);

        $report = Report::factory()->create([
            'reportable_type' => Post::class,
            'reportable_id' => $post->id,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.reports.block-user', $report))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue($author->fresh()->is_blocked);
        $this->assertSame(Report::STATUS_REVIEWED, $report->fresh()->status);

        // The blocked author is kicked out on their next request (FR-6).
        // Refresh first: actingAs() reuses the in-memory model.
        $this->actingAs($author->refresh())
            ->get(route('feed.index'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error');
    }

    public function test_a_report_can_be_dismissed_or_marked_reviewed(): void
    {
        $admin = User::factory()->admin()->create();
        $dismissed = Report::factory()->create();
        $reviewed = Report::factory()->create();

        $this->actingAs($admin)
            ->patch(route('admin.reports.dismiss', $dismissed))
            ->assertSessionHas('success');

        $this->assertSame(Report::STATUS_DISMISSED, $dismissed->fresh()->status);

        $this->actingAs($admin)
            ->patch(route('admin.reports.reviewed', $reviewed))
            ->assertSessionHas('success');

        $this->assertSame(Report::STATUS_REVIEWED, $reviewed->fresh()->status);
    }

    public function test_deleting_the_content_removes_the_content_and_its_reports(): void
    {
        $admin = User::factory()->admin()->create();
        $post = Post::factory()->create();
        $report = Report::factory()->create([
            'reportable_type' => Post::class,
            'reportable_id' => $post->id,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.reports.content.destroy', $report))
            ->assertRedirect(route('admin.reports.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
        $this->assertDatabaseCount('reports', 0);
    }

    public function test_the_admin_dashboard_shows_the_pending_reports(): void
    {
        $admin = User::factory()->admin()->create();
        Report::factory()->create();
        Report::factory()->reviewed()->create();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $this->assertSame(1, $response->viewData('pendingReports'));
        $this->assertCount(1, $response->viewData('recentReports'));
    }
}
