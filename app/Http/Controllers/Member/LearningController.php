<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LearningController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Get user's purchases with course and progress data
        $purchases = $user->purchases()
            ->with(['course'])
            ->where('status', 'paid')
            ->orderBy('created_at', 'desc')
            ->get();

        // Map to MyCourse format for frontend
        $courses = $purchases->map(function ($purchase) {
            return [
                'id' => $purchase->course->id,
                'name' => $purchase->course->name,
                'thumbnail' => $purchase->course->thumbnail,
                'instructor_name' => $purchase->course->instructor_name,
                'progress_percent' => 0,
                'purchased_at' => $purchase->created_at->toIso8601String(),
            ];
        });

        return Inertia::render('Member/Learning', [
            'courses' => $courses,
        ]);
    }
}
