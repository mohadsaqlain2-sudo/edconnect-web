<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateClassStatusRequest;
use App\Models\ClassRoom;
use App\Models\User;
use App\Notifications\ClassStarted;
use Illuminate\Http\RedirectResponse;

/**
 * The teacher starts, ends or reschedules a class (FR-21).
 * Only the transitions listed in ClassRoom::TRANSITIONS are accepted.
 */
class ClassStatusController extends Controller
{
    /**
     * Move the class to another status.
     */
    public function update(UpdateClassStatusRequest $request, ClassRoom $classRoom): RedirectResponse
    {
        // UpdateClassStatusRequest authorizes against ClassPolicy and validates
        // the status against ClassRoom::STATUSES.
        $status = (string) $request->validated('status');

        // Not every change makes sense: completed -> scheduled for example.
        if (! $classRoom->canTransitionTo($status)) {
            return back()->with('error', __('The class cannot move from :from to :to.', [
                'from' => __($classRoom->status),
                'to' => __($status),
            ]));
        }

        $attributes = ['status' => $status];

        // Going live is a real event: stamp the actual start moment next to the
        // planned date + time (they are kept as they are) so the room, the
        // auto-complete command and the audit trail can tell "planned" from
        // "really started" (FR-21).
        if ($status === ClassRoom::STATUS_LIVE) {
            $attributes['started_at'] = now();
        }

        $classRoom->update($attributes);

        // Everybody enrolled learns about it at once, so they can join (FR-23).
        if ($status === ClassRoom::STATUS_LIVE) {
            $this->notifyEnrolledStudents($classRoom);
        }

        return back()->with('success', __('The class is now :status.', ['status' => __($status)]));
    }

    /**
     * Tell every enrolled student that the class just went live (FR-23).
     * Blocked accounts receive nothing.
     */
    private function notifyEnrolledStudents(ClassRoom $classRoom): void
    {
        $classRoom->loadMissing('teacher');

        $teacher = $classRoom->teacher;

        if ($teacher === null) {
            return;
        }

        $classRoom->students()
            ->where('users.is_blocked', false)
            ->get()
            ->each(static fn (User $student) => $student->notify(
                new ClassStarted($teacher, $classRoom)
            ));
    }
}
