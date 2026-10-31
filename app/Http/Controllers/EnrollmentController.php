<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * A student joins or leaves a class (FR-17).
 * The unique index on (class_id, student_id) makes a double enrollment
 * impossible, even on a double click.
 */
class EnrollmentController extends Controller
{
    /**
     * Enroll the current student in the class.
     */
    public function store(Request $request, ClassRoom $classRoom): RedirectResponse
    {
        // Also blocks hidden classes (FR-28).
        $this->authorize('view', $classRoom);

        // No point in joining a lesson that is already over.
        if ($classRoom->status === ClassRoom::STATUS_COMPLETED) {
            return back()->with('error', __('This class is already over.'));
        }

        $classRoom->students()->syncWithoutDetaching([$request->user()->id]);

        return back()->with('success', __('You are enrolled in this class.'));
    }

    /**
     * Leave the class again.
     */
    public function destroy(Request $request, ClassRoom $classRoom): RedirectResponse
    {
        $classRoom->students()->detach($request->user()->id);

        return back()->with('success', __('You have left this class.'));
    }
}
