<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(): Response
    {
        $courses = Course::visible()
            ->ordered()
            ->select(['id', 'name', 'tagline', 'price', 'thumbnail', 'instructor_name', 'type', 'status'])
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
            ]);

        return Inertia::render('Home', [
            'courses' => $courses,
        ]);
    }
}
