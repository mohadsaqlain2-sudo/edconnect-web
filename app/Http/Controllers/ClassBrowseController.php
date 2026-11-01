<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Browsing classes as a student: the discover list and the class detail page
 * (FR-17, FR-18, FR-20).
 */
class ClassBrowseController extends Controller
{
    /** Classes per page on the discover page. */
    private const PER_PAGE = 9;

    /**
     * Upcoming (and currently running) classes, with a search box and a
     * subject + teacher filter (FR-17).
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));

        // A very long query is pointless (and wasteful); cap it before the
        // LIKE pattern is built.
        $search = mb_substr($search, 0, 100);

        $subject = $request->query('subject');
        $teacher = $request->query('teacher');
        $date = trim((string) $request->query('date'));

        // Anything that is not YYYY-MM-DD is ignored instead of erroring.
        $date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1 ? $date : '';

        // Anything that is not a positive integer is ignored as well.
        $teacher = is_scalar($teacher) && ctype_digit((string) $teacher) && (int) $teacher > 0
            ? (int) $teacher
            : 0;

        $query = ClassRoom::query()
            ->visible()
            ->with('teacher:id,name,subject')
            ->withCount('students');

        // Search in the title, in the subject and in the teacher's name.
        // LIKE wildcards in the user's text are escaped, so searching for "%"
        // or "_" matches those characters instead of everything.
        if ($search !== '') {
            $pattern = $this->likePattern($search);

            $query->where(function (Builder $inner) use ($pattern): void {
                $inner->whereRaw("title LIKE ? ESCAPE '!'", [$pattern])
                    ->orWhereRaw("subject LIKE ? ESCAPE '!'", [$pattern])
                    ->orWhereHas('teacher', fn (Builder $teacher) => $teacher
                        ->whereRaw("name LIKE ? ESCAPE '!'", [$pattern]));
            });
        }

        // Filter on one subject.
        if (is_string($subject) && $subject !== '') {
            $query->where('subject', $subject);
        }

        // Filter on one teacher.
        if ($teacher > 0) {
            $query->where('teacher_id', $teacher);
        }

        // Filter on one calendar day.
        if ($date !== '') {
            $query->whereDate('date', $date);
        }

        // Only classes that are running right now or still ahead of us.
        $query->where(function (Builder $inner): void {
            $inner->where('status', ClassRoom::STATUS_LIVE)
                ->orWhere(fn (Builder $soon) => $soon->upcoming());
        });

        $classes = $query
            ->orderBy('date')
            ->orderBy('time')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('classes.discover', [
            'classes' => $classes,
            'subjects' => $this->subjects(),
            'teachers' => $this->teachers(),
            'search' => $search,
            'subject' => is_string($subject) ? $subject : '',
            'teacher' => $teacher,
            'date' => $date,
            // So the cards can show "Enrolled" without a query each.
            'enrolledIds' => $this->enrolledIds($request),
        ]);
    }

    /**
     * One class with its details and the enroll / join buttons (FR-17, FR-18).
     */
    public function show(Request $request, ClassRoom $classRoom): View
    {
        $this->authorize('view', $classRoom);

        $user = $request->user();

        $classRoom->load('teacher:id,name,subject,photo,bio')->loadCount('students');

        $isOwner = $classRoom->teacher_id === $user->id;
        $isEnrolled = $user->isStudent() && $user->isEnrolledIn($classRoom);

        return view('classes.show', [
            'classRoom' => $classRoom,
            'isOwner' => $isOwner,
            'isEnrolled' => $isEnrolled,
            // The room opens for the teacher at any time, for a student only
            // while the class is live (FR-20). Admins moderate, they never join.
            'canJoin' => $isOwner || ($isEnrolled && $classRoom->isLive()),
            // A short preview; the full list lives on classes.students.
            'previewStudents' => $classRoom->students()
                ->orderBy('name')
                ->limit(8)
                ->get(['users.id', 'users.name', 'users.photo', 'users.role']),
        ]);
    }

    /**
     * "My enrolled classes" for a student (FR-17): everything they joined,
     * running classes first, then the ones still ahead, then the past ones.
     */
    public function enrolled(Request $request): View
    {
        $student = $request->user();

        $classes = $student->enrolledClasses()
            ->with('teacher:id,name,subject')
            ->withCount('students')
            ->orderByRaw("CASE status WHEN 'live' THEN 0 WHEN 'scheduled' THEN 1 ELSE 2 END")
            ->orderBy('date')
            ->orderBy('time')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('classes.enrolled', [
            'classes' => $classes,
            // Every card on this page is enrolled by definition.
            'enrolledIds' => $student->enrolledClasses()->pluck('classes.id'),
        ]);
    }

    /* ---------------------------------------------------------------
     | Helpers
     * --------------------------------------------------------------- */

    /**
     * All subjects that are actually used, for the filter drop down.
     *
     * @return Collection<int, string>
     */
    private function subjects(): Collection
    {
        return ClassRoom::query()
            ->visible()
            ->whereNotNull('subject')
            ->distinct()
            ->orderBy('subject')
            ->pluck('subject');
    }

    /**
     * All teachers who have at least one visible class, for the filter.
     *
     * @return Collection<int, User>
     */
    private function teachers(): Collection
    {
        return User::query()
            ->whereIn('id', ClassRoom::query()->visible()->select('teacher_id'))
            ->orderBy('name')
            ->get(['id', 'name', 'subject']);
    }

    /**
     * Ids of the classes the current student is already enrolled in.
     *
     * @return Collection<int, int>
     */
    private function enrolledIds(Request $request): Collection
    {
        $user = $request->user();

        return $user !== null && $user->isStudent()
            ? $user->enrolledClasses()->pluck('classes.id')
            : collect();
    }

    /**
     * Escape LIKE wildcards in a search term (MySQL and SQLite safe).
     */
    private function likePattern(string $term): string
    {
        return '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term).'%';
    }
}
