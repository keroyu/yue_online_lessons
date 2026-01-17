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
            ->get();

        return Inertia::render('Home', [
            'courses' => $courses,
        ]);
    }
}
