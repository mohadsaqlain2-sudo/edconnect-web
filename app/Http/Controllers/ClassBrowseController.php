<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
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
     * subject filter (FR-17).
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));
        $subject = $request->query('subject');
        $date = trim((string) $request->query('date'));

        // Anything that is not YYYY-MM-DD is ignored instead of erroring.
        $date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1 ? $date : '';

        $query = ClassRoom::query()
            ->visible()
            ->with('teacher:id,name,subject')
            ->withCount('students');

        // Search in the title and in the teacher's name.
        if ($search !== '') {
            $query->where(function (Builder $inner) use ($search): void {
                $inner->where('title', 'like', '%'.$search.'%')
                    ->orWhereHas('teacher', fn (Builder $teacher) => $teacher->where('name', 'like', '%'.$search.'%'));
            });
        }

        // Filter on one subject.
        if (is_string($subject) && $subject !== '') {
            $query->where('subject', $subject);
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
            'search' => $search,
            'subject' => is_string($subject) ? $subject : '',
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
            // while the class is live (FR-20).
            'canJoin' => $isOwner || $user->isAdmin() || ($isEnrolled && $classRoom->isLive()),
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
}
