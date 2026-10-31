<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use App\Support\DashboardMetrics;
use Illuminate\View\View;

/**
 * Admin dashboard (FR-2, FR-26, FR-33 chart and moderation).
 *
 * Phase 3B keeps the five-key stats contract and the 12-month "chart" data,
 * then adds trends, sparklines, four themed charts and the moderation/activity
 * panels. Expensive aggregates are cached for five minutes in DashboardMetrics.
 */
class AdminDashboardController extends Controller
{
    public function __invoke(DashboardMetrics $metrics): View
    {
        // The cached chart set always contains the 12 month series used by the
        // original test contract.
        $charts = $metrics->adminCharts('12m');

        return view('dashboards.admin', [
            'stats' => $metrics->adminStats(),
            'chart' => $charts['newUsers'],
            'trends' => $metrics->adminTrends(),
            'sparklines' => $metrics->adminSparklines(),

            // Themed chart series (FR-31).
            'roleChart' => $charts['roles'],
            'postsChart' => $charts['posts'],
            'classesChart' => $charts['classes'],

            // Newest accounts for the "recent users" table (FR-26).
            'recentUsers' => User::query()
                ->latest()
                ->take(5)
                ->get(['id', 'name', 'email', 'role', 'photo', 'created_at']),

            // Moderation shortcuts (FR-33): cached pending counter + newest five.
            'pendingReports' => $metrics->adminPendingReports(),
            'recentReports' => Report::query()
                ->pending()
                ->with(['reporter:id,name', 'reportable'])
                ->latest()
                ->take(5)
                ->get(),

            // Latest activity panels (FR-26, FR-33).
            'recentPosts' => Post::query()
                ->visible()
                ->with('user:id,name,photo,role')
                ->withCount(['likes', 'comments'])
                ->latest()
                ->take(5)
                ->get(),
            'liveClasses' => ClassRoom::query()
                ->visible()
                ->where('status', ClassRoom::STATUS_LIVE)
                ->whereDate('date', now()->toDateString())
                ->with('teacher:id,name,photo')
                ->withCount('students')
                ->orderBy('time')
                ->take(4)
                ->get(),
        ]);
    }
}
