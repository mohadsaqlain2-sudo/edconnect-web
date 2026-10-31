<?php

namespace App\Http\Controllers;

use App\Support\DashboardMetrics;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * JSON endpoints behind the dashboard range selectors (Phase 3B).
 *
 * Every route lives inside its role's existing middleware group, so a teacher
 * cannot read the admin series and a student cannot read the teacher series.
 * The returned object is keyed by the chart "series" names used on the page:
 *   admin   newUsers, posts, roles, classes
 *   teacher enrollments, followers
 *   student activity
 */
class DashboardChartController extends Controller
{
    public function admin(Request $request, DashboardMetrics $metrics): JsonResponse
    {
        $range = $metrics->normalizeRange($request->query('range'));

        return response()->json([
            'range' => $range,
            ...$metrics->adminCharts($range),
        ]);
    }

    public function teacher(Request $request, DashboardMetrics $metrics): JsonResponse
    {
        $range = $metrics->normalizeRange($request->query('range'));

        return response()->json([
            'range' => $range,
            ...$metrics->teacherCharts($request->user(), $range),
        ]);
    }

    public function student(Request $request, DashboardMetrics $metrics): JsonResponse
    {
        $range = $metrics->normalizeRange($request->query('range'));

        return response()->json([
            'range' => $range,
            ...$metrics->studentCharts($request->user(), $range),
        ]);
    }
}
