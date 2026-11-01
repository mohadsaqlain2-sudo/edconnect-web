<?php

namespace App\Http\Controllers;

use App\Models\ClassEnrollment;
use App\Models\ClassRoom;
use App\Models\Comment;
use App\Models\Follow;
use App\Models\Like;
use App\Models\User;
use App\Support\DashboardMetrics;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Teacher dashboard (FR-2, FR-29).
 *
 * Phase 3B adds the "next class" hero, a seven-day schedule, enrollment
 * progress, two charts and a richer activity timeline. The original four-key
 * stats array, the classes paginator and the activity collection are kept
 * exactly as they were.
 */
class TeacherDashboardController extends Controller
{
    /** How many activity entries the stream shows. */
    private const ACTIVITY_LIMIT = 10;

    /** Classes per page in the table. */
    private const CLASSES_PER_PAGE = 8;

    public function __invoke(Request $request, DashboardMetrics $metrics): View
    {
        $teacher = $request->user();
        $mine = $teacher->classes();

        // Hero: a class that is live right now wins over the next scheduled one.
        $nextClass = (clone $mine)
            ->where('status', ClassRoom::STATUS_LIVE)
            ->withCount('students')
            ->with(['students' => fn ($query) => $query->orderBy('name')->limit(6)])
            ->orderByDesc('date')
            ->orderByDesc('time')
            ->first();

        if (! $nextClass) {
            $nextClass = (clone $mine)
                ->upcoming()
                ->withCount('students')
                ->with(['students' => fn ($query) => $query->orderBy('name')->limit(6)])
                ->first();
        }

        // The progress bars show enrollment relative to the teacher's biggest
        // class, because there is no capacity column in Phase 3.
        $maxEnrollment = (int) ClassEnrollment::query()
            ->whereIn('class_id', (clone $mine)->select('id'))
            ->selectRaw('class_id, COUNT(*) as total')
            ->groupBy('class_id')
            ->pluck('total')
            ->max();

        $maxEnrollment = max(1, $maxEnrollment);

        $charts = $metrics->teacherCharts($teacher, '12m');

        // My classes, newest first, with the number of enrolled students.
        // withCount() keeps the table free of N+1 queries.
        $classes = $mine
            ->withCount('students')
            ->orderByDesc('date')
            ->orderByDesc('time')
            ->paginate(self::CLASSES_PER_PAGE)
            ->withQueryString();

        return view('dashboards.teacher', [
            'stats' => $metrics->teacherStats($teacher),
            'trends' => $metrics->teacherTrends($teacher),
            'classes' => $classes,
            'activity' => $this->recentActivity($teacher),
            'nextClass' => $nextClass,
            'maxEnrollment' => $maxEnrollment,
            'schedule' => $metrics->teacherSchedule($teacher),
            'enrollmentChart' => $charts['enrollments'],
            'followerChart' => $charts['followers'],
        ]);
    }

    /**
     * The latest likes, comments and new followers that concern this teacher.
     * Each source is limited to ACTIVITY_LIMIT rows and eager loads its author,
     * then the three lists are merged and cut down to the newest entries.
     *
     * @return Collection<int, array{icon: string, color: string, text: string, excerpt: string|null, at: CarbonInterface|null}>
     */
    private function recentActivity(User $teacher): Collection
    {
        // Likes on my posts.
        $likes = Like::query()
            ->with(['user:id,name', 'post:id,user_id,body'])
            ->whereHas('post', fn ($query) => $query->where('user_id', $teacher->id))
            ->latest()
            ->limit(self::ACTIVITY_LIMIT)
            ->get()
            ->map(fn (Like $like) => [
                'icon' => 'bi-hand-thumbs-up',
                'color' => 'primary',
                'text' => __(':name liked your post', ['name' => $like->user?->name ?? __('Someone')]),
                'excerpt' => $like->post?->body,
                'at' => $like->created_at,
            ]);

        // Comments on my posts.
        $comments = Comment::query()
            ->with(['user:id,name', 'post:id,user_id,body'])
            ->whereHas('post', fn ($query) => $query->where('user_id', $teacher->id))
            ->latest()
            ->limit(self::ACTIVITY_LIMIT)
            ->get()
            ->map(fn (Comment $comment) => [
                'icon' => 'bi-chat',
                'color' => 'success',
                'text' => __(':name commented on your post', ['name' => $comment->user?->name ?? __('Someone')]),
                'excerpt' => $comment->body,
                'at' => $comment->created_at,
            ]);

        // Students who enrolled in one of my classes.
        $enrollments = ClassEnrollment::query()
            ->with(['student:id,name', 'classRoom:id,teacher_id,title'])
            ->whereHas('classRoom', fn ($query) => $query->where('teacher_id', $teacher->id))
            ->latest()
            ->limit(self::ACTIVITY_LIMIT)
            ->get()
            ->map(fn (ClassEnrollment $enrollment) => [
                'icon' => 'bi-person-check',
                'color' => 'info',
                'text' => __(':name enrolled in :class', [
                    'name' => $enrollment->student?->name ?? __('Someone'),
                    'class' => $enrollment->classRoom?->title ?? __('your class'),
                ]),
                'excerpt' => null,
                'at' => $enrollment->created_at,
            ]);

        // People who started following me.
        $followers = Follow::query()
            ->with('follower:id,name')
            ->where('following_id', $teacher->id)
            ->latest()
            ->limit(self::ACTIVITY_LIMIT)
            ->get()
            ->map(fn (Follow $follow) => [
                'icon' => 'bi-person-plus',
                'color' => 'warning',
                'text' => __(':name started following you', ['name' => $follow->follower?->name ?? __('Someone')]),
                'excerpt' => null,
                'at' => $follow->created_at,
            ]);

        return $likes
            ->concat($comments)
            ->concat($enrollments)
            ->concat($followers)
            ->sortByDesc('at')
            ->take(self::ACTIVITY_LIMIT)
            ->values();
    }
}
