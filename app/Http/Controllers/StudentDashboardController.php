<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\Follow;
use App\Models\Post;
use App\Models\User;
use App\Support\DashboardMetrics;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Student dashboard (FR-2, FR-30).
 *
 * Phase 3B adds the next-class hero with a live countdown, upcoming class
 * cards, recommended classes, a learning-activity chart and a three-post feed
 * preview. The original stats array, upcomingClasses collection and
 * recommendedTeachers collection keep their exact shape.
 */
class StudentDashboardController extends Controller
{
    /** How many upcoming classes the list shows. */
    private const UPCOMING_LIMIT = 6;

    /** How many teacher suggestions the list shows. */
    private const RECOMMENDATION_LIMIT = 3;

    /** How many recommended classes the student sees. */
    private const CLASS_RECOMMENDATION_LIMIT = 3;

    /** How many feed posts the preview shows. */
    private const FEED_PREVIEW_LIMIT = 3;

    public function __invoke(Request $request, DashboardMetrics $metrics): View
    {
        $student = $request->user();

        // Upcoming classes I am enrolled in, soonest first.
        // Eager loading the teacher and counting the students avoids N+1.
        $upcomingClasses = $student->enrolledClasses()
            ->upcoming()
            ->with('teacher:id,name,subject,photo')
            ->withCount('students')
            ->limit(self::UPCOMING_LIMIT)
            ->get();

        // Hero: the next scheduled class, falling back to a class that is live.
        $nextClass = $upcomingClasses->first();

        if (! $nextClass) {
            $nextClass = $student->enrolledClasses()
                ->where('classes.status', ClassRoom::STATUS_LIVE)
                ->with('teacher:id,name,subject,photo')
                ->withCount('students')
                ->first();
        }

        $heroClass = $nextClass?->load([
            'students' => fn ($query) => $query->orderBy('name')->limit(6),
        ]);

        $followingIds = $student->following()->pluck('users.id');

        $charts = $metrics->studentCharts($student, '12m');

        return view('dashboards.student', [
            'stats' => $metrics->studentStats($student),
            'upcomingClasses' => $upcomingClasses,
            'recommendedTeachers' => $this->recommendedTeachers($student),
            'recommendedClasses' => $this->recommendedClasses($student),
            'heroClass' => $heroClass,
            'classmates' => $heroClass?->students ?? collect(),
            'followingIds' => $followingIds,
            'notificationCount' => $student->unreadNotifications()->count(),
            'activityChart' => $charts['activity'],
            'feedPreview' => $this->feedPreview($student),
        ]);
    }

    /**
     * Teachers that this student does not follow yet, most followed first.
     *
     * @return EloquentCollection<int, User>
     */
    private function recommendedTeachers(User $student): EloquentCollection
    {
        // Sub-query of everybody the student already follows.
        $alreadyFollowed = Follow::query()
            ->select('following_id')
            ->where('follower_id', $student->id);

        return User::query()
            ->where('role', User::ROLE_TEACHER)
            ->whereNotIn('users.id', $alreadyFollowed)
            ->withCount('followers')
            ->orderByDesc('followers_count')
            ->orderBy('name')
            ->limit(self::RECOMMENDATION_LIMIT)
            ->get();
    }

    /**
     * Classes from followed teachers or from subjects the student already
     * studies, excluding anything they are enrolled in.
     *
     * @return EloquentCollection<int, ClassRoom>
     */
    private function recommendedClasses(User $student): EloquentCollection
    {
        $followedTeacherIds = $student->following()
            ->where('users.role', User::ROLE_TEACHER)
            ->pluck('users.id');

        $subjects = $student->enrolledClasses()
            ->whereNotNull('classes.subject')
            ->distinct()
            ->pluck('classes.subject');

        if ($followedTeacherIds->isEmpty() && $subjects->isEmpty()) {
            return new EloquentCollection;
        }

        $enrolledIds = $student->enrolledClasses()->select('classes.id');

        return ClassRoom::query()
            ->visible()
            ->upcoming()
            ->whereNotIn('classes.id', $enrolledIds)
            ->where(function ($query) use ($followedTeacherIds, $subjects): void {
                if ($followedTeacherIds->isNotEmpty()) {
                    $query->whereIn('teacher_id', $followedTeacherIds);
                }

                if ($subjects->isNotEmpty()) {
                    $query->orWhereIn('subject', $subjects);
                }
            })
            ->with('teacher:id,name,subject,photo')
            ->withCount('students')
            ->limit(self::CLASS_RECOMMENDATION_LIMIT)
            ->get();
    }

    /**
     * The newest posts from people the student follows.
     *
     * @return EloquentCollection<int, Post>
     */
    private function feedPreview(User $student): EloquentCollection
    {
        $following = $student->following()->pluck('users.id');

        if ($following->isEmpty()) {
            return new EloquentCollection;
        }

        return Post::query()
            ->visible()
            ->whereIn('user_id', $following)
            ->withFeedData($student)
            ->latest()
            ->limit(self::FEED_PREVIEW_LIMIT)
            ->get();
    }
}
