<?php

namespace Tests\Feature;

use App\Models\ClassEnrollment;
use App\Models\ClassRoom;
use App\Models\Comment;
use App\Models\Follow;
use App\Models\Hashtag;
use App\Models\Like;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * One test per key relationship / database rule of Phase 2.
 */
class ModelRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    /* ---------------------------------------------------------------
     | Posts
     * --------------------------------------------------------------- */

    public function test_a_post_belongs_to_a_user_and_has_comments_likes_and_hashtags(): void
    {
        $author = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $author->id]);

        $comment = Comment::factory()->create(['post_id' => $post->id]);
        $like = Like::factory()->create(['post_id' => $post->id]);
        $hashtag = Hashtag::factory()->create();

        $post->hashtags()->attach($hashtag->id);

        // Post -> belongsTo user
        $this->assertTrue($post->user->is($author));
        // Post -> hasMany comments / likes
        $this->assertTrue($post->comments->first()->is($comment));
        $this->assertTrue($post->likes->first()->is($like));
        // Post <-> belongsToMany hashtags (both directions)
        $this->assertTrue($post->hashtags->first()->is($hashtag));
        $this->assertTrue($hashtag->posts->first()->is($post));
        // User -> hasMany posts
        $this->assertTrue($author->posts->first()->is($post));
    }

    public function test_a_comment_belongs_to_a_post_and_a_user(): void
    {
        $post = Post::factory()->create();
        $author = User::factory()->create();

        $comment = Comment::factory()->create([
            'post_id' => $post->id,
            'user_id' => $author->id,
        ]);

        $this->assertTrue($comment->post->is($post));
        $this->assertTrue($comment->user->is($author));
        $this->assertTrue($author->comments->first()->is($comment));
    }

    public function test_the_visible_scope_hides_hidden_posts_and_comments(): void
    {
        // The comments hang off the visible post, so both tables have exactly
        // one visible and one hidden row.
        $post = Post::factory()->create();
        Post::factory()->hidden()->create();

        Comment::factory()->create(['post_id' => $post->id]);
        Comment::factory()->hidden()->create(['post_id' => $post->id]);

        // The scope keeps only the visible rows.
        $this->assertSame(1, Post::visible()->count());
        $this->assertSame(1, Comment::visible()->count());

        // Both rows are still in the table, they are just hidden.
        $this->assertSame(2, Post::count());
    }

    public function test_deleting_a_post_deletes_its_comments_and_likes(): void
    {
        $post = Post::factory()->create();

        Comment::factory()->count(2)->create(['post_id' => $post->id]);
        Like::factory()->count(2)->create(['post_id' => $post->id]);

        $post->delete();

        // ON DELETE CASCADE.
        $this->assertDatabaseCount('comments', 0);
        $this->assertDatabaseCount('likes', 0);
    }

    /* ---------------------------------------------------------------
     | Likes
     * --------------------------------------------------------------- */

    public function test_is_liked_by_is_only_true_for_users_who_liked_the_post(): void
    {
        $post = Post::factory()->create();
        $liker = User::factory()->create();
        $other = User::factory()->create();

        Like::factory()->create(['post_id' => $post->id, 'user_id' => $liker->id]);

        $this->assertTrue($post->isLikedBy($liker));
        $this->assertFalse($post->isLikedBy($other));
        $this->assertFalse($post->isLikedBy(null));

        // Also correct when the feed eager loaded the relation.
        $loaded = Post::with('likes')->findOrFail($post->id);
        $this->assertTrue($loaded->isLikedBy($liker));
    }

    public function test_a_post_can_only_be_liked_once_by_the_same_user(): void
    {
        $post = Post::factory()->create();
        $user = User::factory()->create();

        Like::factory()->create(['post_id' => $post->id, 'user_id' => $user->id]);

        // unique(post_id, user_id)
        $this->expectException(QueryException::class);

        Like::factory()->create(['post_id' => $post->id, 'user_id' => $user->id]);
    }

    /* ---------------------------------------------------------------
     | Follows
     * --------------------------------------------------------------- */

    public function test_a_user_can_follow_and_unfollow_another_user(): void
    {
        $follower = User::factory()->create();
        $followed = User::factory()->create();

        $this->assertFalse($follower->isFollowing($followed));

        $this->assertTrue($follower->follow($followed));

        $this->assertTrue($follower->fresh()->isFollowing($followed));
        $this->assertTrue($follower->following->first()->is($followed));   // who I follow
        $this->assertTrue($followed->followers->first()->is($follower));   // who follows me

        $follower->unfollow($followed);

        $this->assertFalse($follower->fresh()->isFollowing($followed));
        $this->assertDatabaseCount('follows', 0);
    }

    public function test_a_user_cannot_follow_themselves(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($user->follow($user));
        $this->assertFalse($user->isFollowing($user));
        $this->assertDatabaseCount('follows', 0);
    }

    public function test_following_the_same_user_twice_creates_only_one_row(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();

        $this->assertTrue($a->follow($b));
        $this->assertTrue($a->follow($b)); // silently ignored by syncWithoutDetaching

        $this->assertDatabaseCount('follows', 1);
    }

    public function test_the_follows_table_rejects_duplicate_rows(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();

        Follow::factory()->create(['follower_id' => $a->id, 'following_id' => $b->id]);

        // unique(follower_id, following_id)
        $this->expectException(QueryException::class);

        Follow::factory()->create(['follower_id' => $a->id, 'following_id' => $b->id]);
    }

    /* ---------------------------------------------------------------
     | Classes and enrollments
     * --------------------------------------------------------------- */

    public function test_a_class_room_belongs_to_a_teacher_and_has_enrolled_students(): void
    {
        $teacher = User::factory()->teacher()->create();
        $students = User::factory()->student()->count(3)->create();

        $classRoom = ClassRoom::factory()->create(['teacher_id' => $teacher->id]);
        $classRoom->students()->attach($students->pluck('id')->all());

        // The model keeps the SRS table name.
        $this->assertSame('classes', $classRoom->getTable());

        $this->assertTrue($classRoom->teacher->is($teacher));
        $this->assertTrue($teacher->classes->first()->is($classRoom));

        $this->assertSame(3, $classRoom->students()->count());
        $this->assertSame(3, $classRoom->enrollments()->count());

        $student = $students->first();
        $this->assertTrue($student->enrolledClasses->first()->is($classRoom));
        $this->assertTrue($student->isEnrolledIn($classRoom));
        $this->assertTrue($classRoom->isOwnedBy($teacher));
        $this->assertFalse($classRoom->isOwnedBy($student));
    }

    public function test_a_student_can_only_enroll_once_in_the_same_class(): void
    {
        $classRoom = ClassRoom::factory()->create();
        $student = User::factory()->student()->create();

        ClassEnrollment::factory()->create([
            'class_id' => $classRoom->id,
            'student_id' => $student->id,
        ]);

        // unique(class_id, student_id)
        $this->expectException(QueryException::class);

        ClassEnrollment::factory()->create([
            'class_id' => $classRoom->id,
            'student_id' => $student->id,
        ]);
    }

    public function test_the_upcoming_scope_returns_future_scheduled_classes_only(): void
    {
        // Freeze "now" so the test can never be flaky around midnight.
        $this->travelTo(Carbon::parse('2026-06-15 12:00:00'));

        $teacher = User::factory()->teacher()->create();

        $laterToday = ClassRoom::factory()->create([
            'teacher_id' => $teacher->id,
            'status' => ClassRoom::STATUS_SCHEDULED,
            'date' => '2026-06-15',
            'time' => '15:00:00',
        ]);

        $earlierToday = ClassRoom::factory()->create([
            'teacher_id' => $teacher->id,
            'status' => ClassRoom::STATUS_SCHEDULED,
            'date' => '2026-06-15',
            'time' => '09:00:00',
        ]);

        $futureDay = ClassRoom::factory()->create([
            'teacher_id' => $teacher->id,
            'status' => ClassRoom::STATUS_SCHEDULED,
            'date' => '2026-06-18',
            'time' => '10:00:00',
        ]);

        // Not scheduled -> never upcoming.
        ClassRoom::factory()->live()->create(['teacher_id' => $teacher->id]);
        ClassRoom::factory()->completed()->create(['teacher_id' => $teacher->id]);

        $ids = ClassRoom::upcoming()->pluck('id')->all();

        $this->assertSame([$laterToday->id, $futureDay->id], $ids);
        $this->assertNotContains($earlierToday->id, $ids);
    }

    /* ---------------------------------------------------------------
     | Reports
     * --------------------------------------------------------------- */

    public function test_reports_are_polymorphic_and_belong_to_a_reporter(): void
    {
        $postReport = Report::factory()->forPost()->create();
        $commentReport = Report::factory()->forComment()->create();

        $this->assertInstanceOf(Post::class, $postReport->reportable);
        $this->assertInstanceOf(Comment::class, $commentReport->reportable);

        // morphMany on both content types.
        $this->assertTrue($postReport->reportable->reports->first()->is($postReport));
        $this->assertTrue($commentReport->reportable->reports->first()->is($commentReport));

        // belongsTo reporter, and the inverse hasMany on the user.
        $this->assertInstanceOf(User::class, $postReport->reporter);
        $this->assertTrue($postReport->reporter->reports->first()->is($postReport));

        // Default status and the moderation scope.
        $this->assertSame(Report::STATUS_PENDING, $postReport->status);
        $this->assertSame(2, Report::pending()->count());
    }

    /* ---------------------------------------------------------------
     | Phase 2 helpers, scopes and accessors
     * --------------------------------------------------------------- */

    public function test_a_user_has_teaching_classes_enrolled_classes_likes_and_reports(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        $post = Post::factory()->create(['user_id' => $teacher->id]);

        $taught = ClassRoom::factory()->create(['teacher_id' => $teacher->id]);
        $enrolled = ClassRoom::factory()->create();
        $enrolled->students()->attach($student->id);

        $like = Like::factory()->create(['post_id' => $post->id, 'user_id' => $student->id]);
        $report = Report::factory()->forPost()->create([
            'reporter_id' => $student->id,
            'reportable_id' => $post->id,
        ]);

        // User -> hasMany teachingClasses.
        $this->assertTrue($teacher->teachingClasses->first()->is($taught));
        // User <-> belongsToMany enrolledClasses.
        $this->assertTrue($student->enrolledClasses->first()->is($enrolled));
        // User -> hasMany likes.
        $this->assertTrue($student->likes->first()->is($like));
        // User -> hasMany reports.
        $this->assertTrue($student->reports->first()->is($report));
    }

    public function test_the_follower_and_following_counters_are_correct(): void
    {
        $user = User::factory()->create();
        $followed = User::factory()->count(2)->create();
        $fan = User::factory()->create();

        foreach ($followed as $target) {
            $user->follow($target);
        }

        $fan->follow($user);

        $this->assertSame(2, $user->followingCount());
        $this->assertSame(1, $user->followersCount());
        $this->assertSame(2, $user->following_count);
        $this->assertSame(1, $user->followers_count);

        // withCount() values are reused instead of running another query.
        $counted = User::withCount(['followers', 'following'])->findOrFail($user->id);
        $this->assertSame(1, $counted->followersCount());
        $this->assertSame(2, $counted->followingCount());
    }

    public function test_a_user_without_a_photo_gets_the_default_avatar(): void
    {
        $user = User::factory()->create(['photo' => null]);

        $this->assertSame(asset('images/default-avatar.svg'), $user->photoUrl());
        $this->assertSame(asset('images/default-avatar.svg'), $user->photo_url);
        $this->assertFileExists(public_path('images/default-avatar.svg'));

        $user->photo = 'photos/avatar.jpg';
        $this->assertSame(Storage::disk('public')->url('photos/avatar.jpg'), $user->photoUrl());
    }

    public function test_liked_by_and_with_counts_scopes_work(): void
    {
        $post = Post::factory()->create();
        $liker = User::factory()->create();

        Like::factory()->count(2)->create(['post_id' => $post->id]);
        Like::factory()->create(['post_id' => $post->id, 'user_id' => $liker->id]);
        Comment::factory()->count(3)->create(['post_id' => $post->id]);

        $this->assertTrue($post->likedBy($liker));
        $this->assertFalse($post->likedBy(User::factory()->create()));

        $counted = Post::withCounts()->findOrFail($post->id);
        $this->assertSame(3, (int) $counted->likes_count);
        $this->assertSame(3, (int) $counted->comments_count);
    }

    public function test_a_class_room_can_be_filtered_by_status_and_exposes_its_start_time(): void
    {
        $scheduled = ClassRoom::factory()->create([
            'status' => ClassRoom::STATUS_SCHEDULED,
            'date' => '2026-06-15',
            'time' => '15:30:00',
            'duration' => 60,
        ]);
        ClassRoom::factory()->live()->create();
        ClassRoom::factory()->completed()->create();

        $this->assertSame(1, ClassRoom::byStatus(ClassRoom::STATUS_SCHEDULED)->count());
        $this->assertTrue($scheduled->start_datetime->equalTo(Carbon::parse('2026-06-15 15:30:00')));
        $this->assertTrue($scheduled->endsAt()->equalTo(Carbon::parse('2026-06-15 16:30:00')));
    }

    public function test_every_class_room_gets_a_unique_unguessable_room_name(): void
    {
        $first = ClassRoom::factory()->create(['room_name' => null]);
        $second = ClassRoom::factory()->create(['room_name' => null]);

        $this->assertStringStartsWith('educonnect-', $first->room_name);
        $this->assertSame(31, strlen($first->room_name));
        $this->assertNotSame($first->room_name, $second->room_name);
        $this->assertDatabaseCount('classes', 2);
    }

    public function test_hashtags_are_stored_lowercase_without_the_hash(): void
    {
        $hashtag = Hashtag::create(['name' => '#Maths']);

        $this->assertSame('maths', $hashtag->name);
        $this->assertSame('maths', $hashtag->fresh()->name);

        // unique(name)
        $this->expectException(QueryException::class);

        Hashtag::create(['name' => 'MATHS']);
    }

    public function test_the_follow_model_rejects_a_direct_self_follow_row(): void
    {
        $user = User::factory()->create();

        $this->expectException(InvalidArgumentException::class);

        Follow::create([
            'follower_id' => $user->id,
            'following_id' => $user->id,
        ]);
    }

    public function test_report_details_are_stored(): void
    {
        $report = Report::factory()->forPost()->create([
            'reason' => 'spam',
            'details' => 'Same link posted five times.',
            'status' => Report::STATUS_REVIEWED,
        ]);

        $this->assertSame('Same link posted five times.', $report->fresh()->details);
        $this->assertSame(Report::STATUS_REVIEWED, $report->fresh()->status);
    }
}
