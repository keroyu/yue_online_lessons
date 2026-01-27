<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(): Response
    {
        $user = auth()->user();
        $isAdmin = $user && $user->isAdmin();

        $courses = Course::visibleToUser($user)
            ->ordered()
            ->select(['id', 'name', 'tagline', 'price', 'thumbnail', 'instructor_name', 'type', 'status', 'is_published'])
            ->get()
            ->map(fn ($course) => [
                'id' => $course->id,
                'name' => $course->name,
                'tagline' => $course->tagline,
                'price' => $course->price,
                'thumbnail' => $course->thumbnail_url,
                'instructor_name' => $course->instructor_name,
                'type' => $course->type,
                'status' => $course->status,
                'is_published' => $course->is_published,
            ]);

        return Inertia::render('Home', [
            'courses' => $courses,
            'isAdmin' => $isAdmin,
        ]);
    }
}
