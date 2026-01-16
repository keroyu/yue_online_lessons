<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CourseController extends Controller
{
    public function show(Course $course): Response
    {
        // Return 404 if course is not published (unless user can view unpublished)
        if (!$course->is_published) {
            $user = auth()->user();
            if (!$user || (!$user->isAdmin() && !$user->isEditor())) {
                throw new NotFoundHttpException('Course not found');
            }
        }

        return Inertia::render('Course/Show', [
            'course' => [
                'id' => $course->id,
                'name' => $course->name,
                'tagline' => $course->tagline,
                'description' => $course->description,
                'price' => $course->price,
                'thumbnail' => $course->thumbnail,
                'instructor_name' => $course->instructor_name,
                'type' => $course->type,
                'portaly_url' => $course->portaly_url,
            ],
        ]);
    }
}
