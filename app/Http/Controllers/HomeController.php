<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Services\SubstackRssService;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __construct(
        private SubstackRssService $substackRssService
    ) {}

    public function index(): Response
    {
        $user = auth()->user();
        $isAdmin = $user && $user->isAdmin();

        $courses = Course::visibleToUser($user)
            ->ordered()
            ->select(['id', 'name', 'tagline', 'price', 'original_price', 'promo_ends_at', 'thumbnail', 'instructor_name', 'type', 'status', 'is_published', 'is_visible'])
            ->get()
            ->map(fn ($course) => [
                'id' => $course->id,
                'name' => $course->name,
                'tagline' => $course->tagline,
                'price' => $course->price,
                'original_price' => $course->original_price,
                'is_promo_active' => $course->is_promo_active,
                'thumbnail' => $course->thumbnail_url,
                'instructor_name' => $course->instructor_name,
                'type' => $course->type,
                'status' => $course->status,
                'is_published' => $course->is_published,
                'is_visible' => $course->is_visible,
            ]);

        return Inertia::render('Home', [
            'courses' => $courses,
            'substackArticles' => $this->substackRssService->getArticles(),
            'isAdmin' => $isAdmin,
        ]);
    }
}
