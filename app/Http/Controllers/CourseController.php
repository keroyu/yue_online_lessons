<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Course;
use App\Models\Purchase;
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
        $isDrip = $course->is_drip;
        $hasPreviewLessons = !$isDraft && !$isDrip && $course->hasPreviewLessons();
        $subscription = $course->subscriptionForUser($user);
        $userSubscription = $subscription?->status;
        $canSubscribe = $course->canUserSubscribe($user);

        view()->share('og', [
            'title' => $course->name . ' - Your Time Bank',
            'description' => $course->meta_description ?: $course->tagline ?: $course->name,
            'image' => $course->thumbnail_url,
            'url' => route('course.show', $course),
            'type' => 'website',
        ]);

        return Inertia::render('Course/Show', [
            'course' => [
                'id' => $course->id,
                'name' => $course->name,
                'tagline' => $course->tagline,
                'description' => $course->description,
                'description_md' => $course->description_md,
                'price' => $course->price,
                'original_price' => $course->original_price,
                'promo_ends_at' => $course->promo_ends_at?->toISOString(),
                'is_promo_active' => $course->is_promo_active,
                'thumbnail' => $course->thumbnail_url,
                'instructor_name' => $course->instructor_name,
                'product_type' => $course->type,
                'delivery_mode' => $course->course_type,
                'status' => $course->status,
                'is_published' => $course->is_published,
                'duration_formatted' => $course->duration_formatted,
                'portaly_url' => $course->portaly_url,
                'portaly_product_id' => $course->portaly_product_id,
                'payment_gateway' => $course->payment_gateway,
                'use_payuni' => !$course->portaly_product_id && $course->price > 0,
                'is_free' => !$course->portaly_product_id && $course->price == 0,
                'display_price' => (float) $course->display_price,
                'is_high_ticket' => $course->is_high_ticket,
                'high_ticket_hide_price' => $course->high_ticket_hide_price,
            ],
            'hasPurchased' => $course->hasPaidAccessForUser($user),
            'isOwned'      => $course->hasPaidAccessForUser($user),
            'isInCart'     => $user ? CartItem::where('user_id', $user->id)->where('course_id', $course->id)->exists() : false,
            'isAdmin' => $isAdmin,
            'isPreviewMode' => $isPreviewMode,
            'isHidden' => !$course->is_visible,
            'isDrip' => $isDrip,
            'hasPreviewLessons' => $hasPreviewLessons,
            'userSubscription' => $userSubscription,
            'canSubscribe' => $canSubscribe,
        ]);
    }
}
