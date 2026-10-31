<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClassRequest;
use App\Http\Requests\UpdateClassRequest;
use App\Models\ClassRoom;
use App\Models\User;
use App\Notifications\NewClassFromFollowedTeacher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * A teacher manages their own live classes here (FR-15, FR-16, FR-21).
 * Create, list, edit, delete and the list of enrolled students.
 */
class ClassController extends Controller
{
    /** Classes per page. */
    private const PER_PAGE = 12;

    /** Students per page on the enrolled students page. */
    private const STUDENTS_PER_PAGE = 20;

    /**
     * "My classes": everything this teacher created, newest first.
     */
    public function index(Request $request): View
    {
        $classes = $request->user()->classes()
            // withCount keeps the card free of one query per class (no N+1).
            ->withCount('students')
            ->orderByRaw("CASE status WHEN 'live' THEN 0 WHEN 'scheduled' THEN 1 ELSE 2 END")
            ->orderBy('date')
            ->orderBy('time')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('classes.index', ['classes' => $classes]);
    }

    /**
     * The "create a class" form.
     */
    public function create(): View
    {
        return view('classes.create');
    }

    /**
     * Store a new class and give it a hard to guess Jitsi room (FR-19).
     */
    public function store(StoreClassRequest $request): RedirectResponse
    {
        $classRoom = $request->user()->classes()->create([
            ...$request->validated(),
            // Never trust the client for the room name.
            'room_name' => ClassRoom::generateRoomName(),
            'status' => ClassRoom::STATUS_SCHEDULED,
        ]);

        // Everybody who follows this teacher gets a heads up (FR-23).
        $this->notifyFollowers($request->user(), $classRoom);

        return redirect()
            ->route('classes.show', $classRoom)
            ->with('success', __('Your class has been created.'));
    }

    /**
     * Queue a notification for each follower of the teacher (FR-23).
     * Blocked accounts receive nothing.
     */
    private function notifyFollowers(User $teacher, ClassRoom $classRoom): void
    {
        $teacher->followers()
            ->where('users.is_blocked', false)
            ->get()
            ->each(static fn (User $follower) => $follower->notify(
                new NewClassFromFollowedTeacher($teacher, $classRoom)
            ));
    }

    /**
     * The edit form. ClassPolicy decides who gets here (FR-16).
     */
    public function edit(ClassRoom $classRoom): View
    {
        $this->authorize('update', $classRoom);

        return view('classes.edit', ['classRoom' => $classRoom]);
    }

    /**
     * Save the changes to a class.
     */
    public function update(UpdateClassRequest $request, ClassRoom $classRoom): RedirectResponse
    {
        $this->authorize('update', $classRoom);

        // room_name and status are not part of validated(), so they stay as they are.
        $classRoom->update($request->validated());

        return redirect()
            ->route('classes.show', $classRoom)
            ->with('success', __('Your class has been updated.'));
    }

    /**
     * Delete a class. Enrollments disappear through the cascade rule (FR-16).
     */
    public function destroy(ClassRoom $classRoom): RedirectResponse
    {
        $this->authorize('delete', $classRoom);

        $classRoom->delete();

        return redirect()
            ->route('classes.index')
            ->with('success', __('Your class has been deleted.'));
    }

    /**
     * Everybody who enrolled in this class (FR-29).
     * Only the teacher of the class (or an admin) may look at this.
     */
    public function students(ClassRoom $classRoom): View
    {
        $this->authorize('update', $classRoom);

        $classRoom->load('teacher:id,name,subject')->loadCount('students');

        $students = $classRoom->students()
            ->orderBy('name')
            ->paginate(self::STUDENTS_PER_PAGE)
            ->withQueryString();

        return view('classes.students', [
            'classRoom' => $classRoom,
            'students' => $students,
        ]);
    }
}
