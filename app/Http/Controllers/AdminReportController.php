<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Report;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * The moderation queue (FR-33, FR-34).
 *
 * Every action also moves the report to the matching status:
 *  - hide / unhide / block the author -> Reviewed
 *  - dismiss                          -> Dismissed
 *  - delete the content               -> the report is removed with the content
 */
class AdminReportController extends Controller
{
    private const PER_PAGE = 20;

    /**
     * All reports with a status, type and text filter (FR-33).
     */
    public function index(Request $request): View
    {
        $status = in_array($request->query('status'), Report::STATUSES, true)
            ? (string) $request->query('status')
            : null;

        $type = in_array($request->query('type'), ['post', 'comment'], true)
            ? (string) $request->query('type')
            : null;

        $term = trim((string) $request->query('q', ''));

        $reports = Report::query()
            ->with(['reporter:id,name,email', 'reportable'])
            ->when($status, fn (Builder $query) => $query->where('status', $status))
            ->when($type, fn (Builder $query) => $query->where(
                'reportable_type',
                $type === 'post' ? Post::class : Comment::class
            ))
            ->when($term !== '', function (Builder $query) use ($term): void {
                $pattern = $this->likePattern($term);

                $query->where(function (Builder $inner) use ($pattern): void {
                    $inner->whereRaw("reason LIKE ? ESCAPE '!'", [$pattern])
                        ->orWhereRaw("details LIKE ? ESCAPE '!'", [$pattern])
                        ->orWhereHas('reporter', fn (Builder $reporter) => $reporter
                            ->whereRaw("name LIKE ? ESCAPE '!'", [$pattern]))
                        ->orWhereHasMorph('reportable', [Post::class, Comment::class], fn (Builder $content) => $content
                            ->whereRaw("body LIKE ? ESCAPE '!'", [$pattern])
                            // The offending author's name is searchable too.
                            ->orWhereHas('user', fn (Builder $author) => $author
                                ->whereRaw("name LIKE ? ESCAPE '!'", [$pattern])));
                });
            })
            ->latest()
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        // Small counters for the filter pills.
        $counts = Report::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.reports.index', [
            'reports' => $reports,
            'status' => $status ?? '',
            'type' => $type ?? '',
            'term' => $term,
            'counts' => $counts,
        ]);
    }

    /**
     * One report plus every other report about the same content (FR-33).
     */
    public function show(Report $report): View
    {
        $report->load(['reporter:id,name,email', 'reportable']);

        $related = collect();

        if ($report->reportable) {
            $related = Report::query()
                ->whereKeyNot($report->id)
                ->where('reportable_type', $report->reportable_type)
                ->where('reportable_id', $report->reportable_id)
                ->with('reporter:id,name')
                ->latest()
                ->get();
        }

        return view('admin.reports.show', [
            'report' => $report,
            'related' => $related,
        ]);
    }

    /**
     * Hide the reported post or comment and mark the report reviewed.
     */
    public function hide(Report $report): RedirectResponse
    {
        $content = $this->content($report);

        $content->update(['is_hidden' => true]);
        $report->update(['status' => Report::STATUS_REVIEWED]);

        return back()->with('success', __('The :type has been hidden and the report marked as reviewed.', [
            'type' => strtolower($report->content_type),
        ]));
    }

    /**
     * Make hidden content visible again.
     */
    public function unhide(Report $report): RedirectResponse
    {
        $content = $this->content($report);

        $content->update(['is_hidden' => false]);
        $report->update(['status' => Report::STATUS_REVIEWED]);

        return back()->with('success', __('The :type is visible again.', [
            'type' => strtolower($report->content_type),
        ]));
    }

    /**
     * Delete the reported content. Reports about it disappear with it.
     */
    public function destroyContent(Report $report): RedirectResponse
    {
        $content = $this->content($report);

        $type = $report->reportable_type;
        $id = $report->reportable_id;

        if ($content instanceof Post && $content->image) {
            Storage::disk('public')->delete($content->image);
        }

        $content->delete();

        Report::query()
            ->where('reportable_type', $type)
            ->where('reportable_id', $id)
            ->delete();

        return redirect()
            ->route('admin.reports.index')
            ->with('success', __('The content and all its reports have been deleted.'));
    }

    /**
     * Block the author of the reported content (FR-33).
     */
    public function blockUser(Report $report): RedirectResponse
    {
        $user = $report->reported_user;

        if (! $user) {
            return back()->with('error', __('The author of this content no longer exists.'));
        }

        // Admins are never blocked from the moderation queue either (FR-27).
        if ($user->isAdmin()) {
            return back()->with('error', __('Administrator accounts cannot be blocked.'));
        }

        $this->authorize('block', $user);

        $user->update(['is_blocked' => true]);

        // Kill the active sessions of the blocked account right away.
        if (config('session.driver') === 'database') {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }

        $report->update(['status' => Report::STATUS_REVIEWED]);

        return back()->with('success', __(':name has been blocked and the report marked as reviewed.', [
            'name' => $user->name,
        ]));
    }

    /**
     * Mark the report as reviewed without touching the content.
     */
    public function markReviewed(Report $report): RedirectResponse
    {
        $report->update(['status' => Report::STATUS_REVIEWED]);

        return back()->with('success', __('The report has been marked as reviewed.'));
    }

    /**
     * Dismiss the report (FR-34).
     */
    public function dismiss(Report $report): RedirectResponse
    {
        $report->update(['status' => Report::STATUS_DISMISSED]);

        return back()->with('success', __('The report has been dismissed.'));
    }

    /* ---------------------------------------------------------------
     | Helpers
     * --------------------------------------------------------------- */

    /**
     * The reported post or comment. A missing content row is a 404 because
     * there is nothing left to moderate.
     */
    private function content(Report $report): Post|Comment
    {
        $content = $report->reportable;

        abort_unless($content instanceof Post || $content instanceof Comment, 404);

        return $content;
    }

    /**
     * Escape LIKE wildcards in a search term (MySQL and SQLite safe).
     */
    private function likePattern(string $term): string
    {
        return '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term).'%';
    }
}
