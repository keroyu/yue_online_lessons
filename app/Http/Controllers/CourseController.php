<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\DripSubscription;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CourseController extends Controller
{
    public function show(Course $course): Response
    {
        $user = auth()->user();
        $isAdmin = $user && $user->isAdmin();

        // Draft courses: only admin can view
        $isDraft = $course->status === 'draft' || !$course->is_published;

        if ($isDraft && !$isAdmin) {
            throw new NotFoundHttpException('Course not found');
        }

        // Preview mode: draft course being viewed by admin
        $isPreviewMode = $isDraft && $isAdmin;

        // Drip course subscription info
        $isDrip = $course->course_type === 'drip';
        $userSubscription = null;
        $canSubscribe = false;

        if ($isDrip && $user) {
            $subscription = DripSubscription::where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->first();

            if ($subscription) {
                $userSubscription = $subscription->status;
            } else {
                $canSubscribe = true;
            }
        } elseif ($isDrip && !$user) {
            $canSubscribe = true;
        }

        return Inertia::render('Course/Show', [
            'course' => [
                'id' => $course->id,
                'name' => $course->name,
                'tagline' => $course->tagline,
                'description' => $course->description,
                'description_html' => $course->description_html,
                'price' => $course->price,
                'original_price' => $course->original_price,
                'promo_ends_at' => $course->promo_ends_at?->toISOString(),
                'is_promo_active' => $course->is_promo_active,
                'thumbnail' => $course->thumbnail_url,
                'instructor_name' => $course->instructor_name,
                'type' => $course->type,
                'course_type' => $course->course_type,
                'status' => $course->status,
                'is_published' => $course->is_published,
                'duration_formatted' => $course->duration_formatted,
                'portaly_url' => $course->portaly_url,
                'portaly_product_id' => $course->portaly_product_id,
            ],
            'isAdmin' => $isAdmin,
            'isPreviewMode' => $isPreviewMode,
            'isDrip' => $isDrip,
            'userSubscription' => $userSubscription,
            'canSubscribe' => $canSubscribe,
        ]);
    }
}
