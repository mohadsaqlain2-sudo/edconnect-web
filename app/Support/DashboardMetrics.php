<?php

namespace App\Support;

use App\Models\ClassEnrollment;
use App\Models\ClassRoom;
use App\Models\Follow;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Aggregated dashboard numbers (Phase 3B).
 *
 * Every method that runs more than a trivial query is cached for five minutes.
 * Keys are scoped per role (and per user for the teacher/student dashboards),
 * so one person's numbers never leak into somebody else's page.
 *
 * The public "stats" arrays keep the exact keys the Phase 3 tests expect:
 *   admin   users, teachers, students, posts, classes
 *   teacher classes, upcoming, students, followers
 *   student classes, upcoming, following
 */
class DashboardMetrics
{
    /** Five minutes, as required by the phase brief. */
    private const TTL_SECONDS = 300;

    private const RANGES = ['7d', '30d', '12m'];

    /* =====================================================================
     | Admin
     * ===================================================================== */

    /**
     * Platform totals (FR-7). Exact five-key contract.
     *
     * @return array{users: int, teachers: int, students: int, posts: int, classes: int}
     */
    public function adminStats(): array
    {
        return Cache::remember('dashboard.admin.stats.v1', self::TTL_SECONDS, function (): array {
            // One aggregate query for the three user counters. SUM(CASE ...)
            // works on MySQL and on the SQLite database used by the test suite.
            $userCounts = User::query()
                ->selectRaw(
                    'COUNT(*) as users,
                     SUM(CASE WHEN role = ? THEN 1 ELSE 0 END) as teachers,
                     SUM(CASE WHEN role = ? THEN 1 ELSE 0 END) as students',
                    [User::ROLE_TEACHER, User::ROLE_STUDENT]
                )
                ->first();

            return [
                'users' => (int) ($userCounts->users ?? 0),
                'teachers' => (int) ($userCounts->teachers ?? 0),
                'students' => (int) ($userCounts->students ?? 0),
                'posts' => Post::query()->count(),
                'classes' => ClassRoom::query()->count(),
            ];
        });
    }

    /**
     * Pending reports badge on the admin dashboard (FR-33).
     * The queue itself stays uncached; only this small counter is.
     */
    public function adminPendingReports(): int
    {
        return (int) Cache::remember(
            'dashboard.admin.pending-reports.v1',
            self::TTL_SECONDS,
            static fn (): int => Report::query()->pending()->count()
        );
    }

    /**
     * Month-over-month movement for the five stat cards.
     *
     * @return array<string, array{percent: int, direction: string}>
     */
    public function adminTrends(): array
    {
        return Cache::remember('dashboard.admin.trends.v1', self::TTL_SECONDS, function (): array {
            $thisMonth = now()->copy()->startOfMonth();
            $lastMonth = now()->copy()->subMonthNoOverflow()->startOfMonth();

            $userNow = User::query()->where('created_at', '>=', $thisMonth)->count();
            $userBefore = User::query()
                ->whereBetween('created_at', [$lastMonth, $thisMonth])
                ->count();

            $teachersNow = User::query()
                ->where('role', User::ROLE_TEACHER)
                ->where('created_at', '>=', $thisMonth)
                ->count();
            $teachersBefore = User::query()
                ->where('role', User::ROLE_TEACHER)
                ->whereBetween('created_at', [$lastMonth, $thisMonth])
                ->count();

            $studentsNow = User::query()
                ->where('role', User::ROLE_STUDENT)
                ->where('created_at', '>=', $thisMonth)
                ->count();
            $studentsBefore = User::query()
                ->where('role', User::ROLE_STUDENT)
                ->whereBetween('created_at', [$lastMonth, $thisMonth])
                ->count();

            $postsNow = Post::query()->where('created_at', '>=', $thisMonth)->count();
            $postsBefore = Post::query()
                ->whereBetween('created_at', [$lastMonth, $thisMonth])
                ->count();

            $classesNow = ClassRoom::query()->where('created_at', '>=', $thisMonth)->count();
            $classesBefore = ClassRoom::query()
                ->whereBetween('created_at', [$lastMonth, $thisMonth])
                ->count();

            return [
                'users' => $this->trend($userNow, $userBefore),
                'teachers' => $this->trend($teachersNow, $teachersBefore),
                'students' => $this->trend($studentsNow, $studentsBefore),
                'posts' => $this->trend($postsNow, $postsBefore),
                'classes' => $this->trend($classesNow, $classesBefore),
            ];
        });
    }

    /**
     * Seven-day sparkline data for every admin stat card.
     *
     * @return array<string, list<int>>
     */
    public function adminSparklines(): array
    {
        return Cache::remember('dashboard.admin.sparklines.v1', self::TTL_SECONDS, function (): array {
            $since = now()->copy()->startOfDay()->subDays(6);

            return [
                'users' => $this->daySeries(
                    User::query()->where('created_at', '>=', $since)->pluck('created_at'),
                    7
                )['values'],
                'teachers' => $this->daySeries(
                    User::query()
                        ->where('role', User::ROLE_TEACHER)
                        ->where('created_at', '>=', $since)
                        ->pluck('created_at'),
                    7
                )['values'],
                'students' => $this->daySeries(
                    User::query()
                        ->where('role', User::ROLE_STUDENT)
                        ->where('created_at', '>=', $since)
                        ->pluck('created_at'),
                    7
                )['values'],
                'posts' => $this->daySeries(
                    Post::query()->where('created_at', '>=', $since)->pluck('created_at'),
                    7
                )['values'],
                'classes' => $this->daySeries(
                    ClassRoom::query()->where('created_at', '>=', $since)->pluck('created_at'),
                    7
                )['values'],
            ];
        });
    }

    /**
     * Every admin chart series for a range.
     *
     * @return array<string, array{labels: list<string>, values: list<int>, total: int}>
     */
    public function adminCharts(string $range = '12m'): array
    {
        $range = $this->normalizeRange($range);

        return Cache::remember("dashboard.admin.charts.{$range}.v1", self::TTL_SECONDS, function () use ($range): array {
            $roles = $this->adminRoleSeries();
            $classes = $this->adminClassSeries();

            return [
                'newUsers' => $this->adminNewUsers($range),
                'posts' => $this->adminPosts($range),
                'roles' => $roles,
                'classes' => $classes,
            ];
        });
    }

    /**
     * New registrations for 7 days, 30 days or 12 months.
     *
     * @return array{labels: list<string>, values: list<int>, total: int}
     */
    public function adminNewUsers(string $range = '12m'): array
    {
        $range = $this->normalizeRange($range);

        $timestamps = User::query()
            ->when($range !== '12m', fn ($query) => $query->where('created_at', '>=', $this->rangeStart($range)))
            ->pluck('created_at');

        return $this->seriesForRange($timestamps, $range);
    }

    /**
     * Posts per day/week/month, depending on the selected range.
     *
     * @return array{labels: list<string>, values: list<int>, total: int}
     */
    public function adminPosts(string $range = '12m'): array
    {
        $range = $this->normalizeRange($range);

        $timestamps = Post::query()
            ->when($range !== '12m', fn ($query) => $query->where('created_at', '>=', $this->rangeStart($range)))
            ->pluck('created_at');

        if ($range === '12m') {
            return $this->weekSeries($timestamps, 12);
        }

        return $this->seriesForRange($timestamps, $range);
    }

    /**
     * Users by role as a doughnut series.
     *
     * @return array{labels: list<string>, values: list<int>, total: int}
     */
    public function adminRoleSeries(): array
    {
        $counts = User::query()
            ->selectRaw('role, COUNT(*) as total')
            ->groupBy('role')
            ->pluck('total', 'role');

        $labels = [];
        $values = [];

        foreach ([User::ROLE_ADMIN, User::ROLE_TEACHER, User::ROLE_STUDENT] as $role) {
            $labels[] = ucfirst($role).'s';
            $values[] = (int) ($counts[$role] ?? 0);
        }

        return ['labels' => $labels, 'values' => $values, 'total' => array_sum($values)];
    }

    /**
     * Classes by status as a doughnut series.
     *
     * @return array{labels: list<string>, values: list<int>, total: int}
     */
    public function adminClassSeries(): array
    {
        $counts = ClassRoom::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $labels = [];
        $values = [];

        foreach (ClassRoom::STATUSES as $status) {
            $labels[] = ucfirst($status);
            $values[] = (int) ($counts[$status] ?? 0);
        }

        return ['labels' => $labels, 'values' => $values, 'total' => array_sum($values)];
    }

    /* =====================================================================
     | Teacher
     * ===================================================================== */

    /**
     * The teacher's own counters. Exact four-key contract.
     *
     * @return array{classes: int, upcoming: int, students: int, followers: int}
     */
    public function teacherStats(User $teacher): array
    {
        return Cache::remember("dashboard.teacher.{$teacher->id}.stats.v1", self::TTL_SECONDS, function () use ($teacher): array {
            $mine = $teacher->classes();

            return [
                'classes' => (clone $mine)->count(),
                'upcoming' => (clone $mine)->upcoming()->count(),
                // Distinct students across all classes (nobody counted twice).
                'students' => ClassEnrollment::query()
                    ->whereIn('class_id', (clone $mine)->select('id'))
                    ->distinct()
                    ->count('student_id'),
                'followers' => $teacher->followers()->count(),
            ];
        });
    }

    /**
     * Month-over-month growth for the teacher cards.
     *
     * @return array<string, array{percent: int, direction: string}>
     */
    public function teacherTrends(User $teacher): array
    {
        return Cache::remember("dashboard.teacher.{$teacher->id}.trends.v1", self::TTL_SECONDS, function () use ($teacher): array {
            $thisMonth = now()->copy()->startOfMonth();
            $lastMonth = now()->copy()->subMonthNoOverflow()->startOfMonth();

            $classesNow = $teacher->classes()->where('created_at', '>=', $thisMonth)->count();
            $classesBefore = $teacher->classes()
                ->whereBetween('created_at', [$lastMonth, $thisMonth])
                ->count();

            $enrollmentsNow = ClassEnrollment::query()
                ->whereIn('class_id', $teacher->classes()->select('id'))
                ->where('created_at', '>=', $thisMonth)
                ->count();
            $enrollmentsBefore = ClassEnrollment::query()
                ->whereIn('class_id', $teacher->classes()->select('id'))
                ->whereBetween('created_at', [$lastMonth, $thisMonth])
                ->count();

            $followersNow = Follow::query()
                ->where('following_id', $teacher->id)
                ->where('created_at', '>=', $thisMonth)
                ->count();
            $followersBefore = Follow::query()
                ->where('following_id', $teacher->id)
                ->whereBetween('created_at', [$lastMonth, $thisMonth])
                ->count();

            return [
                'classes' => $this->trend($classesNow, $classesBefore),
                'students' => $this->trend($enrollmentsNow, $enrollmentsBefore),
                'followers' => $this->trend($followersNow, $followersBefore),
            ];
        });
    }

    /**
     * Chart series for the teacher dashboard.
     *
     * @return array<string, array{labels: list<string>, values: list<int>, total: int}>
     */
    public function teacherCharts(User $teacher, string $range = '12m'): array
    {
        $range = $this->normalizeRange($range);

        return Cache::remember("dashboard.teacher.{$teacher->id}.charts.{$range}.v1", self::TTL_SECONDS, function () use ($teacher, $range): array {
            $classes = $teacher->classes()
                ->withCount('students')
                ->when($range !== '12m', fn ($query) => $query->whereDate('date', '>=', $this->rangeStart($range)))
                ->orderByDesc('students_count')
                ->orderBy('title')
                ->take(8)
                ->get(['id', 'title']);

            $labels = $classes->map(fn (ClassRoom $class) => Str::limit($class->title, 18))->all();
            $values = $classes->map(fn (ClassRoom $class) => (int) $class->students_count)->all();

            $followers = Follow::query()
                ->where('following_id', $teacher->id)
                ->when($range !== '12m', fn ($query) => $query->where('created_at', '>=', $this->rangeStart($range)))
                ->pluck('created_at');

            return [
                'enrollments' => [
                    'labels' => $labels,
                    'values' => $values,
                    'total' => array_sum($values),
                ],
                'followers' => $this->seriesForRange($followers, $range),
            ];
        });
    }

    /**
     * The next seven days with the number of classes on each day.
     *
     * @return list<array{date: Carbon, weekday: string, day: string, count: int, isToday: bool}>
     */
    public function teacherSchedule(User $teacher): array
    {
        return Cache::remember("dashboard.teacher.{$teacher->id}.schedule.v1", self::TTL_SECONDS, function () use ($teacher): array {
            $start = now()->copy()->startOfDay();
            $end = $start->copy()->addDays(6)->endOfDay();

            $counts = $teacher->classes()
                ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                ->pluck('date')
                ->countBy(fn ($date) => Carbon::parse($date)->toDateString());

            $days = [];

            for ($offset = 0; $offset < 7; $offset++) {
                $date = $start->copy()->addDays($offset);

                $days[] = [
                    'date' => $date->copy(),
                    'weekday' => $date->format('D'),
                    'day' => $date->format('j'),
                    'count' => (int) ($counts[$date->toDateString()] ?? 0),
                    'isToday' => $date->isToday(),
                ];
            }

            return $days;
        });
    }

    /* =====================================================================
     | Student
     * ===================================================================== */

    /**
     * The student's counters. Exact three-key contract.
     *
     * @return array{classes: int, upcoming: int, following: int}
     */
    public function studentStats(User $student): array
    {
        return Cache::remember("dashboard.student.{$student->id}.stats.v1", self::TTL_SECONDS, function () use ($student): array {
            return [
                'classes' => $student->enrolledClasses()->count(),
                'upcoming' => $student->enrolledClasses()->upcoming()->count(),
                // Only teachers count, so the card matches what the page offers.
                'following' => $student->following()->where('role', User::ROLE_TEACHER)->count(),
            ];
        });
    }

    /**
     * Classes attended per week (a proxy based on enrolled class dates).
     *
     * @return array<string, array{labels: list<string>, values: list<int>, total: int}>
     */
    public function studentCharts(User $student, string $range = '12m'): array
    {
        $range = $this->normalizeRange($range);

        return Cache::remember("dashboard.student.{$student->id}.charts.{$range}.v1", self::TTL_SECONDS, function () use ($student, $range): array {
            $dates = $student->enrolledClasses()
                ->when($range !== '12m', fn ($query) => $query->whereDate('classes.date', '>=', $this->rangeStart($range)))
                ->select('classes.date')
                ->pluck('classes.date');

            return [
                'activity' => $this->seriesForRange($dates, $range),
            ];
        });
    }

    /* =====================================================================
     | Series helpers
     * ===================================================================== */

    public function normalizeRange(?string $range): string
    {
        return in_array($range, self::RANGES, true) ? $range : '12m';
    }

    /**
     * @return array{labels: list<string>, values: list<int>, total: int}
     */
    private function seriesForRange(Collection $timestamps, string $range): array
    {
        return match ($range) {
            '7d' => $this->daySeries($timestamps, 7),
            '30d' => $this->weekSeries($timestamps, 5),
            default => $this->monthSeries($timestamps, 12),
        };
    }

    /**
     * @return array{labels: list<string>, values: list<int>, total: int}
     */
    private function daySeries(Collection $timestamps, int $days): array
    {
        $start = now()->copy()->startOfDay()->subDays($days - 1);
        $counts = $timestamps->countBy(
            static fn ($timestamp) => Carbon::parse($timestamp)->toDateString()
        );

        $labels = [];
        $values = [];

        for ($offset = 0; $offset < $days; $offset++) {
            $day = $start->copy()->addDays($offset);

            $labels[] = $day->format('d M');
            $values[] = (int) ($counts[$day->toDateString()] ?? 0);
        }

        return ['labels' => $labels, 'values' => $values, 'total' => array_sum($values)];
    }

    /**
     * @return array{labels: list<string>, values: list<int>, total: int}
     */
    private function weekSeries(Collection $timestamps, int $weeks): array
    {
        $start = now()->copy()->startOfWeek()->subWeeks($weeks - 1);
        $counts = $timestamps->countBy(
            static fn ($timestamp) => Carbon::parse($timestamp)->startOfWeek()->toDateString()
        );

        $labels = [];
        $values = [];

        for ($offset = 0; $offset < $weeks; $offset++) {
            $week = $start->copy()->addWeeks($offset);

            $labels[] = $week->format('d M');
            $values[] = (int) ($counts[$week->toDateString()] ?? 0);
        }

        return ['labels' => $labels, 'values' => $values, 'total' => array_sum($values)];
    }

    /**
     * @return array{labels: list<string>, values: list<int>, total: int}
     */
    private function monthSeries(Collection $timestamps, int $months): array
    {
        // First month of the window: 11 months back, so today's month is included.
        $start = now()->copy()->startOfMonth()->subMonths($months - 1);
        $counts = $timestamps->countBy(
            static fn ($timestamp) => Carbon::parse($timestamp)->format('Y-m')
        );

        $labels = [];
        $values = [];

        for ($offset = 0; $offset < $months; $offset++) {
            $month = $start->copy()->addMonths($offset);

            $labels[] = $month->format('M Y');
            $values[] = (int) ($counts[$month->format('Y-m')] ?? 0);
        }

        return ['labels' => $labels, 'values' => $values, 'total' => array_sum($values)];
    }

    private function rangeStart(string $range): Carbon
    {
        return match ($range) {
            '7d' => now()->copy()->startOfDay()->subDays(6),
            '30d' => now()->copy()->startOfDay()->subDays(29),
            default => now()->copy()->startOfMonth()->subMonths(11),
        };
    }

    /**
     * @return array{percent: int, direction: string}
     */
    private function trend(int $current, int $previous): array
    {
        if ($previous === 0) {
            $percent = $current === 0 ? 0 : 100;
        } else {
            $percent = (int) round((($current - $previous) / $previous) * 100);
        }

        return [
            'percent' => $percent,
            'direction' => $percent > 0 ? 'up' : ($percent < 0 ? 'down' : 'flat'),
        ];
    }
}
