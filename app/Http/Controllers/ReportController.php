<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReportRequest;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Report;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;

/**
 * Users report an inappropriate post or comment (FR-32).
 *
 * Rules:
 *  - a user can never report their own content,
 *  - a user can report the same item only once,
 *  - the item must still be visible (a hidden item is not reportable),
 *  - every action ends with a flash message.
 */
class ReportController extends Controller
{
    public function storePost(StoreReportRequest $request, Post $post): RedirectResponse
    {
        return $this->store($request, $post);
    }

    public function storeComment(StoreReportRequest $request, Comment $comment): RedirectResponse
    {
        return $this->store($request, $comment);
    }

    /**
     * Shared implementation for posts and comments.
     */
    private function store(StoreReportRequest $request, Model $reportable): RedirectResponse
    {
        // Policy: the content must belong to somebody else.
        $this->authorize('create', [Report::class, $reportable]);

        // A hidden item is only visible to its author and admins; do not feed
        // the moderation queue with reports about content nobody can see.
        if (($reportable->is_hidden ?? false) === true) {
            abort(404);
        }

        $user = $request->user();

        // One report per user per item (also enforced by a unique index).
        if ($reportable->reports()->where('reporter_id', $user->id)->exists()) {
            return back()->with('error', __('You have already reported this content.'));
        }

        $validated = $request->validated();

        $report = Report::create([
            'reporter_id' => $user->id,
            'reportable_type' => $reportable->getMorphClass(),
            'reportable_id' => $reportable->getKey(),
            'reason' => $validated['reason'],
            'details' => $validated['details'] ?? null,
            'status' => Report::STATUS_PENDING,
        ]);

        return back()->with('success', __('Thank you. Your report (:reason) was sent to the moderators.', [
            'reason' => $report->reason_label,
        ]));
    }
}
