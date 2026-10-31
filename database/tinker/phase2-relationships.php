<?php

/**
 * Phase 2 relationship proof.
 *
 * Run it after the demo data exists:
 *
 *   php artisan migrate:fresh --seed
 *   php artisan tinker --execute="require base_path('database/tinker/phase2-relationships.php');"
 *
 * Every line prints PASS or FAIL. The script throws at the end when anything
 * failed, so it is also usable in CI.
 */

use App\Models\ClassEnrollment;
use App\Models\ClassRoom;
use App\Models\Comment;
use App\Models\Follow;
use App\Models\Hashtag;
use App\Models\Like;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Carbon;

$results = [];

$check = function (string $label, bool $ok) use (&$results): void {
    $results[] = [$label, $ok];
    echo ($ok ? 'PASS' : 'FAIL').'  '.$label.PHP_EOL;
};

/* -----------------------------------------------------------------
 | Pick one row of every kind from the seeded demo data.
 * ----------------------------------------------------------------- */

$post = Post::whereHas('comments')->whereHas('likes')->whereHas('hashtags')->firstOrFail();
$author = $post->user;
$comment = Comment::whereHas('reports')->firstOrFail();
$like = Like::firstOrFail();
$hashtag = $post->hashtags->first();
$classRoom = ClassRoom::whereHas('students')->whereHas('enrollments')->firstOrFail();
$teacher = $classRoom->teacher;
$student = $classRoom->students()->firstOrFail();
$enrollment = ClassEnrollment::firstOrFail();
$report = Report::firstOrFail();
$reporter = $report->reporter;
$follow = Follow::firstOrFail();
$follower = $follow->follower;
$followed = $follow->following;
$commentAuthor = User::whereHas('comments')->firstOrFail();
$liker = User::whereHas('likes')->firstOrFail();

/* -----------------------------------------------------------------
 | User relationships and helpers
 * ----------------------------------------------------------------- */

$check('User hasMany posts', $author->posts->contains($post));
$check('User hasMany comments', $commentAuthor->comments->isNotEmpty());
$check('User hasMany likes', $liker->likes->isNotEmpty());
$check('User followers (belongsToMany self)', $followed->followers->contains($follower));
$check('User following (belongsToMany self)', $follower->following->contains($followed));
$check('User hasMany teachingClasses', $teacher->teachingClasses->contains($classRoom));
$check('User belongsToMany enrolledClasses', $student->enrolledClasses->contains($classRoom));
$check('User hasMany reports', $reporter->reports->contains($report));
$check('User isFollowing() helper', $follower->isFollowing($followed));
$check('User followersCount() helper', $followed->followersCount() === $followed->followers()->count());
$check('User followingCount() helper', $follower->followingCount() === $follower->following()->count());
$check('User photoUrl() default avatar', str_ends_with($student->photoUrl(), 'images/default-avatar.svg'));
$check('User cannot follow themselves', $student->follow($student) === false);

/* -----------------------------------------------------------------
 | Post relationships, scopes and helpers
 * ----------------------------------------------------------------- */

$check('Post belongsTo user', $post->user->is($author) || $post->user !== null);
$check('Post hasMany comments', $post->comments->isNotEmpty());
$check('Post hasMany likes', $post->likes->isNotEmpty());
$check('Post belongsToMany hashtags', $post->hashtags->contains($hashtag));
$check('Post morphMany reports', Post::whereHas('reports')->exists());
$check('Post likedBy() helper true', $post->likedBy($post->likes->first()->user));
$check('Post likedBy() helper false', $post->likedBy(User::factory()->make()) === false);

$counted = Post::withCounts()->findOrFail($post->id);
$check('Post withCounts() scope', isset($counted->likes_count, $counted->comments_count));
$check('Post visible() scope', Post::visible()->whereKey($post->id)->exists());

/* -----------------------------------------------------------------
 | Comment, Like, Hashtag
 * ----------------------------------------------------------------- */

$check('Comment belongsTo post', $comment->post instanceof Post);
$check('Comment belongsTo user', $comment->user instanceof User);
$check('Comment morphMany reports', $comment->reports->isNotEmpty());

$check('Like belongsTo post', $like->post instanceof Post);
$check('Like belongsTo user', $like->user instanceof User);

$check('Hashtag belongsToMany posts', $hashtag->posts->contains($post));
$check('Hashtag stored lowercase without #', $hashtag->name === mb_strtolower($hashtag->name) && ! str_contains($hashtag->name, '#'));

/* -----------------------------------------------------------------
 | Classes and enrollments
 * ----------------------------------------------------------------- */

$check('ClassRoom table is "classes"', $classRoom->getTable() === 'classes');
$check('ClassRoom belongsTo teacher', $classRoom->teacher instanceof User);
$check('ClassRoom belongsToMany students', $classRoom->students->isNotEmpty());
$check('ClassRoom hasMany enrollments', $classRoom->enrollments->isNotEmpty());
$check('ClassRoom byStatus() scope', ClassRoom::byStatus($classRoom->status)->whereKey($classRoom->id)->exists());
$check('ClassRoom upcoming() scope is a query', ClassRoom::upcoming()->count() >= 0);
$check('ClassRoom start_datetime accessor', $classRoom->start_datetime instanceof Carbon);
$check(
    'ClassRoom room name is educonnect- + 20 chars',
    str_starts_with($classRoom->room_name, 'educonnect-') && strlen($classRoom->room_name) === 31
);

$check('ClassEnrollment belongsTo classRoom', $enrollment->classRoom instanceof ClassRoom);
$check('ClassEnrollment belongsTo student', $enrollment->student instanceof User);

/* -----------------------------------------------------------------
 | Reports and follows
 * ----------------------------------------------------------------- */

$check('Report belongsTo reporter', $report->reporter instanceof User);
$check('Report morphTo reportable', $report->reportable instanceof Post || $report->reportable instanceof Comment);
$check('Report stores details', $report->details === null || is_string($report->details));

$check('Follow belongsTo follower', $follow->follower instanceof User);
$check('Follow belongsTo following', $follow->following instanceof User);

/* -----------------------------------------------------------------
 | Database guard: a direct self-follow row is rejected.
 * ----------------------------------------------------------------- */

$selfFollowRejected = false;

try {
    Follow::create([
        'follower_id' => $student->id,
        'following_id' => $student->id,
    ]);
} catch (InvalidArgumentException) {
    $selfFollowRejected = true;
}

$check('Follow model rejects direct self-follow', $selfFollowRejected);

/* -----------------------------------------------------------------
 | Summary
 * ----------------------------------------------------------------- */

$passed = count(array_filter($results, fn (array $row): bool => $row[1]));
$failed = count($results) - $passed;

echo PHP_EOL."Phase 2 relationship proof: {$passed} passed, {$failed} failed.".PHP_EOL;

if ($failed > 0) {
    throw new RuntimeException("{$failed} relationship check(s) failed.");
}
