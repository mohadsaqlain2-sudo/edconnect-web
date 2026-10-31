<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Sends a user to the dashboard that belongs to their role (FR-2).
 * The dashboards themselves live in AdminDashboardController,
 * TeacherDashboardController and StudentDashboardController.
 */
class DashboardController extends Controller
{
    /**
     * /dashboard -> forward the user to the dashboard of their own role.
     */
    public function index(Request $request): RedirectResponse
    {
        return redirect()->route($request->user()->dashboardRoute());
    }
}
