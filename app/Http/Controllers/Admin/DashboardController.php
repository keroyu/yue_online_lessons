<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\User;
use App\Models\Purchase;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the admin dashboard.
     */
    public function index(): Response
    {
        $stats = [
            'total_courses' => Course::count(),
            'published_courses' => Course::where('is_published', true)->count(),
            'draft_courses' => Course::where('status', 'draft')->count(),
            'total_users' => User::where('role', 'member')->count(),
            'total_purchases' => Purchase::count(),
        ];

        $recentCourses = Course::latest()
            ->take(5)
            ->get(['id', 'name', 'status', 'is_published', 'created_at']);

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'recentCourses' => $recentCourses,
        ]);
    }
}
