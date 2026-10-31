<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Hashtag;
use App\Models\Post;
use App\Models\User;
use App\Services\HashtagService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Phase 4: the complete social feed.
 *
 * Covers FR-9 (create post, 280 chars, throttle),
 * FR-10 (edit/delete own posts, image cleanup),
 * FR-11 (feed of followed + own, like toggle),
 * FR-13 (comments + CommentPolicy),
 * FR-14 (public profile),
 * FR-15 (hashtags, XSS safe links, hashtag page) and
 * FR-11 (follow / unfollow, no self-follow).
 */
class FeedTest extends TestCase
{
    use RefreshDatabase;

    /* ---------------------------------------------------------------
     | Creating posts (FR-9)
     * --------------------------------------------------------------- */

    public function test_a_logged_in_user_can_publish_a_post(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('posts.store'), [
            'body' => 'My very first post on EduConnect',
        ]);

        $response->assertRedirect(route('feed.index'));

        $this->assertDatabaseHas('posts', [
            'user_id' => $user->id,
            'body' => 'My very first post on EduConnect',
            'is_hidden' => false,
        ]);
    }

    public function test_a_post_cannot_be_longer_than_280_characters(): void
    {
        $user = User::factory()->create();

        // 281 characters must be rejected by StorePostRequest (FR-9).
        $this->actingAs($user)
            ->post(route('posts.store'), ['body' => str_repeat('a', 281)])
            ->assertSessionHasErrors('body');

        $this->assertDatabaseCount('posts', 0);

        // Exactly 280 characters still works.
        $this->actingAs($user)
            ->post(route('posts.store'), ['body' => str_repeat('a', 280)])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('posts', 1);
    }

    public function test_an_empty_post_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('posts.store'), ['body' => ''])
            ->assertSessionHasErrors('body');

        $this->assertDatabaseCount('posts', 0);
    }

    public function test_a_guest_cannot_publish_a_post(): void
    {
        $this->post(route('posts.store'), ['body' => 'Sneaky'])
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('posts', 0);
    }

    public function test_post_creation_is_rate_limited_to_ten_per_minute(): void
    {
        $user = User::factory()->create();

        // Ten posts inside the same minute are fine (FR-9).
        for ($i = 1; $i <= 10; $i++) {
            $this->actingAs($user)
                ->post(route('posts.store'), ['body' => 'Post number '.$i])
                ->assertRedirect(route('feed.index'));
        }

        // The eleventh one hits the "throttle:10,1" middleware.
        $this->actingAs($user)
            ->post(route('posts.store'), ['body' => 'One too many'])
            ->assertStatus(429);
    }

    /* ---------------------------------------------------------------
     | Editing and deleting (FR-10)
     * --------------------------------------------------------------- */

    public function test_a_user_cannot_edit_someone_elses_post(): void
    {
        $author = User::factory()->create();
        $other = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $author->id,
            'body' => 'Original text',
        ]);

        // The edit form and the update are both refused by PostPolicy (FR-10).
        $this->actingAs($other)->get(route('posts.edit', $post))->assertForbidden();
        $this->actingAs($other)
            ->patch(route('posts.update', $post), ['body' => 'Hacked'])
            ->assertForbidden();

        $this->assertSame('Original text', $post->fresh()->body);
    }

    public function test_a_user_cannot_delete_someone_elses_post(): void
    {
        $author = User::factory()->create();
        $other = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $author->id]);

        $this->actingAs($other)->delete(route('posts.destroy', $post))->assertForbidden();

        $this->assertDatabaseHas('posts', ['id' => $post->id]);
    }

    public function test_the_author_can_edit_and_delete_their_own_post(): void
    {
        $author = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $author->id,
            'body' => 'Before',
        ]);

        $this->actingAs($author)->get(route('posts.edit', $post))->assertOk();

        $this->actingAs($author)
            ->patch(route('posts.update', $post), ['body' => 'After'])
            ->assertRedirect(route('posts.show', $post));

        $this->assertSame('After', $post->fresh()->body);

        $this->actingAs($author)
            ->delete(route('posts.destroy', $post))
            ->assertRedirect(route('feed.index'));

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_deleting_a_post_removes_its_image_file(): void
    {
        Storage::fake('public');

        $author = User::factory()->create();

        $this->actingAs($author)->post(route('posts.store'), [
            'body' => 'A post with a picture',
            // A real JPEG: ImageUploadService decodes and re-encodes every
            // upload, so a fake empty file would be rejected on purpose.
            'image' => UploadedFile::fake()->image('holiday.jpg', 1200, 800),
        ]);

        $post = Post::latest('id')->firstOrFail();

        $this->assertNotNull($post->image);
        Storage::disk('public')->assertExists($post->image);

        $this->actingAs($author)->delete(route('posts.destroy', $post));

        // The uploaded file must not be left behind on the disk (FR-10).
        Storage::disk('public')->assertMissing($post->image);
    }

    /* ---------------------------------------------------------------
     | Likes (FR-11)
     * --------------------------------------------------------------- */

    public function test_a_like_can_be_toggled_and_never_duplicates(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        // Like the post: JSON answer with the new state and count.
        $this->actingAs($user)
            ->postJson(route('posts.like', $post))
            ->assertOk()
            ->assertJson(['liked' => true, 'count' => 1]);

        $this->assertDatabaseHas('likes', [
            'post_id' => $post->id,
            'user_id' => $user->id,
        ]);

        // Liking twice must not create a second row.
        $this->actingAs($user)
            ->postJson(route('posts.like', $post))
            ->assertOk()
            ->assertJson(['liked' => true, 'count' => 1]);

        $this->assertSame(1, $post->likes()->count());

        // Unlike removes the row again.
        $this->actingAs($user)
            ->deleteJson(route('posts.unlike', $post))
            ->assertOk()
            ->assertJson(['liked' => false, 'count' => 0]);

        $this->assertDatabaseMissing('likes', [
            'post_id' => $post->id,
            'user_id' => $user->id,
        ]);
    }

    /* ---------------------------------------------------------------
     | Comments (FR-13)
     * --------------------------------------------------------------- */

    public function test_a_user_can_comment_and_only_delete_their_own_comment(): void
    {
        $postAuthor = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $postAuthor->id]);

        $commenter = User::factory()->create();
        $stranger = User::factory()->create();

        // Add a comment - the JSON answer carries the ready made HTML + count.
        $this->actingAs($commenter)
            ->postJson(route('comments.store', $post), ['body' => 'Great share!'])
            ->assertCreated()
            ->assertJsonPath('count', 1);

        $comment = Comment::latest('id')->firstOrFail();

        $this->assertSame('Great share!', $comment->body);

        // A stranger may not delete it (CommentPolicy).
        $this->actingAs($stranger)
            ->delete(route('comments.destroy', $comment))
            ->assertForbidden();

        $this->assertDatabaseHas('comments', ['id' => $comment->id]);

        // The author of the comment can delete it.
        $this->actingAs($commenter)
            ->deleteJson(route('comments.destroy', $comment))
            ->assertOk()
            ->assertJson(['deleted' => true, 'count' => 0]);

        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    public function test_the_post_author_can_delete_comments_under_their_post(): void
    {
        $postAuthor = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $postAuthor->id]);
        $comment = Comment::factory()->create(['post_id' => $post->id]);

        $this->actingAs($postAuthor)
            ->deleteJson(route('comments.destroy', $comment))
            ->assertOk();

        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    /* ---------------------------------------------------------------
     | Follow / unfollow (FR-11)
     * --------------------------------------------------------------- */

    public function test_a_student_can_follow_and_unfollow_a_teacher(): void
    {
        $student = User::factory()->student()->create();
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($student)
            ->postJson(route('follows.store', $teacher))
            ->assertOk()
            ->assertJson(['following' => true, 'followers' => 1, 'following_count' => 1]);

        $this->assertTrue($student->isFollowing($teacher));

        $this->actingAs($student)
            ->deleteJson(route('follows.destroy', $teacher))
            ->assertOk()
            ->assertJson(['following' => false, 'followers' => 0, 'following_count' => 0]);

        $this->assertFalse($student->isFollowing($teacher));
    }

    public function test_nobody_can_follow_themselves(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)
            ->post(route('follows.store', $student))
            ->assertForbidden();

        $this->assertSame(0, $student->following()->count());
    }

    public function test_a_student_can_follow_another_student(): void
    {
        $student = User::factory()->student()->create();
        $otherStudent = User::factory()->student()->create();

        $this->actingAs($student)
            ->post(route('follows.store', $otherStudent))
            ->assertRedirect();

        $this->assertTrue($student->isFollowing($otherStudent));
    }

    /* ---------------------------------------------------------------
     | Home feed (FR-11)
     * --------------------------------------------------------------- */

    public function test_the_feed_shows_only_my_posts_and_the_posts_of_people_i_follow(): void
    {
        $me = User::factory()->student()->create();
        $followed = User::factory()->teacher()->create();
        $stranger = User::factory()->teacher()->create();

        $me->follow($followed);

        Post::factory()->create(['user_id' => $me->id, 'body' => 'BODY-MINE']);
        Post::factory()->create(['user_id' => $followed->id, 'body' => 'BODY-FOLLOWED']);
        Post::factory()->create(['user_id' => $stranger->id, 'body' => 'BODY-STRANGER']);
        // Hidden posts stay out of the feed even when they are my own (FR-27).
        Post::factory()->hidden()->create(['user_id' => $me->id, 'body' => 'BODY-HIDDEN']);

        $this->actingAs($me)
            ->get(route('feed.index'))
            ->assertOk()
            ->assertSee('BODY-MINE')
            ->assertSee('BODY-FOLLOWED')
            ->assertDontSee('BODY-STRANGER')
            ->assertDontSee('BODY-HIDDEN');
    }

    public function test_the_feed_shows_an_empty_state_without_posts(): void
    {
        $me = User::factory()->create();

        $this->actingAs($me)
            ->get(route('feed.index'))
            ->assertOk()
            ->assertSee('No posts to show yet.');
    }

    public function test_the_feed_answers_json_for_the_load_more_button(): void
    {
        $me = User::factory()->create();

        // 12 posts: page 1 holds 10, page 2 the remaining 2 (FR-11).
        Post::factory()->count(12)->create(['user_id' => $me->id]);

        $this->actingAs($me)
            ->get(route('feed.index'))
            ->assertOk()
            ->assertSee('data-load-more', false);

        $this->actingAs($me)
            ->getJson(route('feed.index', ['page' => 2]))
            ->assertOk()
            ->assertJsonStructure(['html', 'has_more', 'next_page'])
            ->assertJsonPath('has_more', false);
    }

    public function test_a_guest_is_redirected_from_the_feed(): void
    {
        $this->get(route('feed.index'))->assertRedirect(route('login'));
    }

    /* ---------------------------------------------------------------
     | Hashtags (FR-15)
     * --------------------------------------------------------------- */

    public function test_hashtags_are_parsed_and_synced_in_lower_case(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('posts.store'), [
            'body' => 'Learning #Laravel and #PHP today',
        ]);

        $post = Post::latest('id')->firstOrFail();

        // Stored lower case, one row per tag (no duplicates).
        $this->assertDatabaseHas('hashtags', ['name' => 'laravel']);
        $this->assertDatabaseHas('hashtags', ['name' => 'php']);
        $this->assertSame(
            ['laravel', 'php'],
            $post->hashtags()->orderBy('name')->pluck('name')->all()
        );
    }

    public function test_hashtag_links_are_xss_safe(): void
    {
        $service = app(HashtagService::class);

        // The markup is escaped first, then the link is inserted around #php.
        $html = (string) $service->linkify('<script>alert(1)</script> hello #php');

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringContainsString(
            'href="'.route('hashtags.show', ['hashtag' => 'php']).'"',
            $html
        );
    }

    public function test_a_post_body_with_html_is_escaped_on_the_feed(): void
    {
        $user = User::factory()->create();

        Post::factory()->create([
            'user_id' => $user->id,
            'body' => '<script>alert(1)</script> #safety',
        ]);

        $this->actingAs($user)
            ->get(route('feed.index'))
            ->assertOk()
            // The raw script tag must never reach the browser...
            ->assertDontSee('<script>alert(1)</script>', false)
            // ...it arrives as escaped text instead.
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_the_hashtag_page_lists_visible_posts_with_that_tag(): void
    {
        $user = User::factory()->create();
        $service = app(HashtagService::class);

        $visible = Post::factory()->create([
            'user_id' => $user->id,
            'body' => 'VISIBLE-TAG-POST about #laravel',
        ]);
        $service->sync($visible, $visible->body);

        $hidden = Post::factory()->hidden()->create([
            'user_id' => $user->id,
            'body' => 'HIDDEN-TAG-POST about #laravel',
        ]);
        $service->sync($hidden, $hidden->body);

        // An unrelated post with another tag must not show up either.
        $other = Post::factory()->create([
            'user_id' => $user->id,
            'body' => 'OTHER-TAG-POST about #symfony',
        ]);
        $service->sync($other, $other->body);

        $hashtag = Hashtag::where('name', 'laravel')->firstOrFail();
        $this->assertSame('laravel', $hashtag->name);

        $this->actingAs($user)
            ->get(route('hashtags.show', ['hashtag' => 'laravel']))
            ->assertOk()
            ->assertSee('VISIBLE-TAG-POST')
            ->assertDontSee('HIDDEN-TAG-POST')
            ->assertDontSee('OTHER-TAG-POST');
    }

    /* ---------------------------------------------------------------
     | Public profile (FR-14)
     * --------------------------------------------------------------- */

    public function test_a_public_profile_shows_the_user_posts_and_counts(): void
    {
        $teacher = User::factory()->teacher()->create();
        Post::factory()->count(3)->create(['user_id' => $teacher->id]);

        $follower = User::factory()->student()->create();
        $follower->follow($teacher);

        $this->actingAs($follower)
            ->get(route('users.show', $teacher))
            ->assertOk()
            ->assertSee($teacher->name)
            // posts + followers counters in the header.
            ->assertSee('followers')
            ->assertSee(route('users.followers', $teacher), false);
    }

    public function test_the_follower_lists_are_paginated_and_reachable(): void
    {
        $teacher = User::factory()->teacher()->create();
        $follower = User::factory()->student()->create();
        $follower->follow($teacher);

        $this->actingAs($follower)
            ->get(route('users.followers', $teacher))
            ->assertOk()
            ->assertSee($follower->name);

        $this->actingAs($follower)
            ->get(route('users.following', $follower))
            ->assertOk()
            ->assertSee($teacher->name);
    }

    /* ---------------------------------------------------------------
     | Single post page (FR-12)
     * --------------------------------------------------------------- */

    public function test_the_single_post_page_lists_its_comments(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);
        Comment::factory()->create([
            'post_id' => $post->id,
            'body' => 'COMMENT-ON-THE-POST',
        ]);

        $this->actingAs($user)
            ->get(route('posts.show', $post))
            ->assertOk()
            ->assertSee('COMMENT-ON-THE-POST');
    }

    public function test_a_hidden_post_is_not_visible_to_other_users(): void
    {
        $author = User::factory()->create();
        $other = User::factory()->create();
        $post = Post::factory()->hidden()->create(['user_id' => $author->id]);

        // The author still reaches their own hidden post...
        $this->actingAs($author)->get(route('posts.show', $post))->assertOk();
        // ...everybody else gets a 403 (FR-27).
        $this->actingAs($other)->get(route('posts.show', $post))->assertForbidden();
    }
}
