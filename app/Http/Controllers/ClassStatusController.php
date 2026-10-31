<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateClassStatusRequest;
use App\Models\ClassRoom;
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

        $classRoom->update(['status' => $status]);

        return back()->with('success', __('The class is now :status.', ['status' => __($status)]));
    }
}
