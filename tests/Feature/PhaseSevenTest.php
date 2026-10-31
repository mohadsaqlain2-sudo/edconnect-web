<?php

namespace Tests\Feature;

use App\Models\ClassRoom;
use App\Models\Comment;
use App\Models\Hashtag;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 7 - reports, moderation and admin management (FR-27, FR-28, FR-32 - FR-34).
 *
 * The happy paths already live in ReportTest and AdminManagementTest. This file
 * covers the cross-cutting guarantees: the report UI, admin-only moderation
 * actions, hidden content disappearing from every surface and every counter,
 * and blocked users being unable to act anywhere.
 */
class PhaseSevenTest extends TestCase
{
    use RefreshDatabase;

    /* -----------------------------------------------------------------
     | Reporting UI (FR-32)
     * ----------------------------------------------------------------- */

    public function test_every_post_and_comment_offers_a_report_modal(): void
    {
        $author = User::factory()->teacher()->create();
        $viewer = User::factory()->student()->create();
        $viewer->follow($author);

        $post = Post::factory()->create([
            'user_id' => $author->id,
            'body' => 'A post that can be reported.',
        ]);
        $comment = Comment::factory()->create([
            'post_id' => $post->id,
            'user_id' => $author->id,
            'body' => 'A comment that can be reported.',
        ]);

        $response = $this->actingAs($viewer)
            ->get(route('feed.index'))
            ->assertOk();

        // The kebab menu and the comment row both open their own modal.
        $response->assertSee('report-post-'.$post->id, false);
        $response->assertSee('report-comment-'.$comment->id, false);
        $response->assertSee('Report post');
        $response->assertSee('Report comment');

        // Required reason + capped details (FR-32).
        $response->assertSee('name="reason"', false);
        $response->assertSee('Harassment');
        $response->assertSee('Inappropriate content');
        $response->assertSee('Hate speech');
        $response->assertSee('maxlength="500"', false);
    }

    public function test_a_report_cannot_be_filed_twice_on_the_same_comment(): void
    {
        $reporter = User::factory()->student()->create();
        $comment = Comment::factory()->create();

        $this->actingAs($reporter)
            ->post(route('reports.comments.store', $comment), ['reason' => 'spam'])
            ->assertSessionHas('success');

        $this->actingAs($reporter)
            ->post(route('reports.comments.store', $comment), ['reason' => 'harassment'])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('reports', 1);
    }

    /* -----------------------------------------------------------------
     | Moderation actions (FR-33, FR-34)
     * ----------------------------------------------------------------- */

    public function test_moderation_actions_are_admin_only(): void
    {
        $report = Report::factory()->forPost()->create();

        $actions = [
            ['patch', 'admin.reports.hide'],
            ['patch', 'admin.reports.unhide'],
            ['patch', 'admin.reports.reviewed'],
            ['patch', 'admin.reports.dismiss'],
            ['post', 'admin.reports.block-user'],
            ['delete', 'admin.reports.content.destroy'],
        ];

        foreach ([User::factory()->teacher()->create(), User::factory()->student()->create()] as $user) {
            $this->actingAs($user)
                ->get(route('admin.reports.show', $report))
                ->assertForbidden();

            foreach ($actions as [$method, $name]) {
                $this->actingAs($user)
                    ->{$method}(route($name, $report))
                    ->assertForbidden();
            }
        }

        // Nothing changed while the non-admins were being refused.
        $report->refresh();
        $this->assertSame(Report::STATUS_PENDING, $report->status);
        $this->assertFalse((bool) $report->reportable->is_hidden);
    }

    public function test_deleting_a_reported_comment_removes_it_and_its_reports(): void
    {
        $admin = User::factory()->admin()->create();
        $comment = Comment::factory()->create();
        $report = Report::factory()->create([
            'reportable_type' => Comment::class,
            'reportable_id' => $comment->id,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.reports.content.destroy', $report))
            ->assertRedirect(route('admin.reports.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
        $this->assertDatabaseCount('reports', 0);
    }

    public function test_dismissing_a_report_keeps_the_content_visible(): void
    {
        $admin = User::factory()->admin()->create();
        $post = Post::factory()->create();
        $report = Report::factory()->create([
            'reportable_type' => Post::class,
            'reportable_id' => $post->id,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.reports.dismiss', $report))
            ->assertSessionHas('success');

        $this->assertSame(Report::STATUS_DISMISSED, $report->fresh()->status);
        $this->assertFalse($post->fresh()->is_hidden);
    }

    public function test_an_administrator_cannot_be_blocked_from_the_report_queue(): void
    {
        $admin = User::factory()->admin()->create();
        $otherAdmin = User::factory()->admin()->create();

        $post = Post::factory()->create(['user_id' => $otherAdmin->id]);
        $report = Report::factory()->create([
            'reportable_type' => Post::class,
            'reportable_id' => $post->id,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.reports.block-user', $report))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertFalse($otherAdmin->fresh()->is_blocked);
        $this->assertSame(Report::STATUS_PENDING, $report->fresh()->status);
    }

    public function test_the_report_queue_search_matches_the_offending_author(): void
    {
        $admin = User::factory()->admin()->create();
        $offender = User::factory()->teacher()->create(['name' => 'Offender Zoe']);
        $post = Post::factory()->create(['user_id' => $offender->id]);

        Report::factory()->create([
            'reportable_type' => Post::class,
            'reportable_id' => $post->id,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.reports.index', ['q' => 'Zoe']))
            ->assertOk();

        $this->assertSame(1, $response->viewData('reports')->total());
    }

    /* -----------------------------------------------------------------
     | Hidden content disappears everywhere (FR-28)
     * ----------------------------------------------------------------- */

    public function test_hidden_posts_disappear_from_feed_profile_hashtag_and_search(): void
    {
        $admin = User::factory()->admin()->create();
        $author = User::factory()->teacher()->create();
        $follower = User::factory()->student()->create();
        $follower->follow($author);

        $hashtag = Hashtag::factory()->create(['name' => 'moderated']);
        $body = 'moderated marker that must vanish';

        $post = Post::factory()->create([
            'user_id' => $author->id,
            'body' => $body,
        ]);
        $post->hashtags()->attach($hashtag);

        $this->actingAs($admin)
            ->patch(route('admin.posts.hide', $post))
            ->assertSessionHas('success');

        // Feed.
        $this->actingAs($follower)
            ->get(route('feed.index'))
            ->assertOk()
            ->assertDontSee($body);

        // Profile: the card and the header counter.
        $profile = $this->actingAs($follower)
            ->get(route('users.show', $author))
            ->assertOk()
            ->assertDontSee($body);

        $this->assertSame(0, $profile->viewData('profile')->posts_count);

        // Hashtag page (card + total).
        $hashtagPage = $this->actingAs($follower)
            ->get(route('hashtags.show', $hashtag->name))
            ->assertOk()
            ->assertDontSee($body);

        $this->assertSame(0, $hashtagPage->viewData('posts')->total());

        // Search: posts tab and the hashtag post counter.
        $this->actingAs($follower)
            ->get(route('search.index', ['q' => 'marker', 'tab' => 'posts']))
            ->assertOk()
            ->assertDontSee($body);

        $hashtagSearch = $this->actingAs($follower)
            ->get(route('search.index', ['q' => 'moderated', 'tab' => 'hashtags']))
            ->assertOk();

        $this->assertSame(0, $hashtagSearch->viewData('hashtags')->first()->posts_count);

        // A normal visitor cannot open the post directly, but its author still can.
        $this->actingAs($follower)
            ->get(route('posts.show', $post))
            ->assertForbidden();

        $this->actingAs($author)
            ->get(route('posts.show', $post))
            ->assertOk()
            ->assertSee($body);
    }

    public function test_hidden_comments_disappear_from_the_post_and_its_counter(): void
    {
        $admin = User::factory()->admin()->create();
        $post = Post::factory()->create();
        $comment = Comment::factory()->create([
            'post_id' => $post->id,
            'body' => 'a comment that must not be counted',
        ]);

        $report = Report::factory()->create([
            'reportable_type' => Comment::class,
            'reportable_id' => $comment->id,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.reports.hide', $report))
            ->assertSessionHas('success');

        $response = $this->actingAs($admin)
            ->get(route('posts.show', $post))
            ->assertOk()
            ->assertDontSee('a comment that must not be counted');

        $this->assertSame(0, $response->viewData('post')->comments_count);
    }

    /* -----------------------------------------------------------------
     | Blocked accounts (FR-6, section 5.6)
     * ----------------------------------------------------------------- */

    public function test_a_blocked_user_cannot_post_comment_enroll_or_join(): void
    {
        $blocked = User::factory()->student()->blocked()->create();
        $post = Post::factory()->create();
        $classRoom = ClassRoom::factory()->scheduled()->create();
        $liveClass = ClassRoom::factory()->live()->create();
        $liveClass->students()->attach($blocked->id);

        $this->actingAs($blocked)
            ->post(route('posts.store'), ['body' => 'Trying to post while blocked.'])
            ->assertRedirect(route('login'))
            ->assertSessionHas('error');
        $this->assertGuest();

        $this->actingAs($blocked)
            ->post(route('comments.store', $post), ['body' => 'Trying to comment while blocked.'])
            ->assertRedirect(route('login'));
        $this->assertGuest();

        $this->actingAs($blocked)
            ->post(route('classes.enroll', $classRoom))
            ->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertSame(0, $classRoom->students()->count());

        $this->actingAs($blocked)
            ->get(route('classes.room', $liveClass))
            ->assertRedirect(route('login'));
        $this->assertGuest();

        $this->assertDatabaseCount('comments', 0);
        $this->assertSame(0, $post->comments()->count());
    }

    /* -----------------------------------------------------------------
     | Admin filters (FR-27, FR-28)
     * ----------------------------------------------------------------- */

    public function test_the_user_list_can_be_filtered_by_role(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();
        User::factory()->student()->create();

        $response = $this->actingAs($admin)
            ->get(route('admin.users.index', ['role' => User::ROLE_TEACHER]))
            ->assertOk();

        $ids = $response->viewData('users')->pluck('id')->all();

        $this->assertContains($teacher->id, $ids);
        $this->assertNotContains($admin->id, $ids);
    }

    public function test_admin_lists_show_the_author_and_the_report_count(): void
    {
        $admin = User::factory()->admin()->create();
        $author = User::factory()->teacher()->create(['name' => 'Reported Rita']);
        $post = Post::factory()->create([
            'user_id' => $author->id,
            'body' => 'A post with reports on the admin list.',
        ]);

        Report::factory()->count(2)->create([
            'reportable_type' => Post::class,
            'reportable_id' => $post->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.posts.index'))
            ->assertOk()
            ->assertSee('Reported Rita')
            ->assertSee('A post with reports on the admin list.')
            ->assertSee('text-bg-warning">2', false);
    }
}
