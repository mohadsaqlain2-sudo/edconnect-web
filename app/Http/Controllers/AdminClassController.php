<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin class management (FR-28): search, filter, hide/unhide and delete.
 */
class AdminClassController extends Controller
{
    private const PER_PAGE = 20;

    public function index(Request $request): View
    {
        $term = trim((string) $request->query('q', ''));

        $hidden = in_array($request->query('hidden'), ['0', '1'], true)
            ? (string) $request->query('hidden')
            : null;

        $status = in_array($request->query('status'), ClassRoom::STATUSES, true)
            ? (string) $request->query('status')
            : null;

        $classes = ClassRoom::query()
            ->with('teacher:id,name,email')
            ->withCount('students')
            ->when($term !== '', function (Builder $query) use ($term): void {
                $pattern = $this->likePattern($term);

                $query->where(function (Builder $inner) use ($pattern): void {
                    $inner->whereRaw("title LIKE ? ESCAPE '!'", [$pattern])
                        ->orWhereHas('teacher', fn (Builder $teacher) => $teacher
                            ->whereRaw("name LIKE ? ESCAPE '!'", [$pattern]));
                });
            })
            ->when($hidden !== null, fn (Builder $query) => $query->where('is_hidden', $hidden === '1'))
            ->when($status, fn (Builder $query) => $query->where('status', $status))
            ->orderByDesc('date')
            ->orderByDesc('time')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('admin.classes.index', [
            'classes' => $classes,
            'term' => $term,
            'hidden' => $hidden ?? '',
            'status' => $status ?? '',
        ]);
    }

    /**
     * Hide (cancel) or restore a class.
     */
    public function toggleHidden(ClassRoom $classRoom): RedirectResponse
    {
        $classRoom->update(['is_hidden' => ! $classRoom->is_hidden]);

        return back()->with('success', $classRoom->is_hidden
            ? __('The class has been hidden (cancelled).')
            : __('The class is visible again.'));
    }

    /**
     * Delete a class. Enrollments disappear through the cascade rule.
     */
    public function destroy(ClassRoom $classRoom): RedirectResponse
    {
        $this->authorize('delete', $classRoom);

        $classRoom->delete();

        return back()->with('success', __('The class has been deleted.'));
    }

    private function likePattern(string $term): string
    {
        return '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term).'%';
    }
}
