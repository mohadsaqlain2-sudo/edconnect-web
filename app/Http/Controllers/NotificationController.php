<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

/**
 * The notification bell, the notifications page and "mark as read"
 * (FR-23 notifications, FR-24 unread counter, FR-25 read handling).
 *
 * Every lookup goes through $request->user()->notifications(), so one user can
 * never see or change the notifications of somebody else - a wrong id simply
 * answers 404.
 */
class NotificationController extends Controller
{
    /** How many entries the bell dropdown shows. */
    private const DROPDOWN_LIMIT = 8;

    /** Notifications per page on the full page. */
    private const PER_PAGE = 20;

    /**
     * The full notifications page, newest first, with an "unread only" filter.
     */
    public function index(Request $request): View
    {
        // Anything that is not "unread" shows everything.
        $filter = $request->query('filter') === 'unread' ? 'unread' : 'all';

        $notifications = $request->user()
            ->notifications()
            ->when($filter === 'unread', static fn ($query) => $query->whereNull('read_at'))
            ->latest()
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('notifications.index', [
            'notifications' => $notifications,
            'filter' => $filter,
        ]);
    }

    /**
     * Small JSON answer for the polling script in the navbar (FR-24).
     * It carries the fresh unread counter and the newest eight entries as HTML,
     * so one request keeps the whole bell up to date.
     */
    public function poll(Request $request): JsonResponse
    {
        $user = $request->user();

        $notifications = $user->notifications()
            ->latest()
            ->take(self::DROPDOWN_LIMIT)
            ->get();

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'html' => view('notifications._dropdown', ['notifications' => $notifications])->render(),
        ]);
    }

    /**
     * Mark one notification as read (FR-25).
     */
    public function markAsRead(Request $request, string $notification): RedirectResponse|JsonResponse
    {
        $this->findOwn($request, $notification)->markAsRead();

        if ($request->wantsJson()) {
            return response()->json([
                'read' => true,
                'unread_count' => $request->user()->unreadNotifications()->count(),
            ]);
        }

        return back()->with('success', __('Notification marked as read.'));
    }

    /**
     * Mark everything as read (FR-25).
     */
    public function markAllAsRead(Request $request): RedirectResponse|JsonResponse
    {
        // unreadNotifications is a DatabaseNotificationCollection that knows
        // how to mark all of its rows as read in one query.
        $request->user()->unreadNotifications->markAsRead();

        if ($request->wantsJson()) {
            return response()->json(['read' => true, 'unread_count' => 0]);
        }

        return back()->with('success', __('All notifications marked as read.'));
    }

    /**
     * Delete one of MY notifications (FR-25).
     */
    public function destroy(Request $request, string $notification): RedirectResponse|JsonResponse
    {
        $this->findOwn($request, $notification)->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'deleted' => true,
                'unread_count' => $request->user()->unreadNotifications()->count(),
            ]);
        }

        return back()->with('success', __('Notification deleted.'));
    }

    /**
     * Clicking a notification: mark it read, then open the related page (FR-25).
     */
    public function open(Request $request, string $notification): RedirectResponse
    {
        $model = $this->findOwn($request, $notification);
        $model->markAsRead();

        // The target URL was built by the notification itself; fall back to the
        // list when an older entry has none.
        $url = $model->data['url'] ?? null;

        return $url
            ? redirect()->to($url)
            : redirect()->route('notifications.index');
    }

    /**
     * Fetch one of MY notifications or answer 404.
     */
    private function findOwn(Request $request, string $id): DatabaseNotification
    {
        return $request->user()->notifications()->whereKey($id)->firstOrFail();
    }
}
