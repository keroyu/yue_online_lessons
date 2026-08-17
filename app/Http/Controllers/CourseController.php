<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Course;
use App\Models\Purchase;
use App\Services\CouponChainService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CourseController extends Controller
{
    public function __construct(
        protected CouponChainService $couponChainService,
    ) {}

    public function show(Request $request, Course $course): Response
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

        $this->captureTrafficSource($request);

        // Daily view counter (002 US10) — skip admin preview of drafts.
        if (!$isPreviewMode) {
            app(\App\Services\SiteAnalyticsService::class)->recordView($course, $request);
        }

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
                'free_success_md' => $course->free_success_md,
                // {alias} placeholders resolve to each chain's current live code
                'promo_html' => $this->couponChainService->substitutePlaceholders($course->promo_html),
                'promo_delay_seconds' => $course->promo_delay_seconds,
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
                'lessons_count' => $course->lessons()->count(),
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
            'redeemPoints'         => $course->is_redeemable ? (int) $course->redeem_points : null,
            'userAvailablePoints'  => $user ? app(\App\Services\PointService::class)->availableBalance($user) : null,
            'isInCart'     => $user ? CartItem::where('user_id', $user->id)->where('course_id', $course->id)->exists() : false,
            'isAdmin' => $isAdmin,
            'isPreviewMode' => $isPreviewMode,
            'isHidden' => !$course->is_visible,
            'isDrip' => $isDrip,
            'hasPreviewLessons' => $hasPreviewLessons,
            'userSubscription' => $userSubscription,
            'canSubscribe' => $canSubscribe,
            'bookingDraft' => $this->bookingDraft($request, $course),
            // Titles and option labels only — the scores stay on the server
            // (011 FR-124 / D101).
            'screeningQuestions' => $course->is_high_ticket && $course->high_ticket_hide_price
                ? \App\Support\BookingScreening::questionsForFront()
                : null,
        ]);
    }

    /**
     * Pre-fill the application wizard for someone re-applying (011 US9/FR-042).
     *
     * Two ways in, and the difference matters:
     *
     * - `?resume=` — the token from a 「通知新時段」 mail. Holding 64 random
     *   characters is itself the proof of identity, so this works without a
     *   login (waitlisted applicants usually are not members) and returns the
     *   name and email too, plus `resume` so the wizard opens at the picker.
     * - Otherwise, the logged-in owner of the lead. Looking this up by a typed
     *   email would turn the sales page into an "has this address applied?"
     *   oracle, which is not worth saving somebody a retype.
     */
    private function bookingDraft(Request $request, Course $course): ?array
    {
        if (!$course->is_high_ticket || !$course->high_ticket_hide_price) {
            return null;
        }

        $token = $request->query('resume');

        if (is_string($token) && $token !== '') {
            $lead = \App\Models\HighTicketLead::where('resume_token', $token)
                ->where('course_id', $course->id)
                ->first();

            if ($lead) {
                return array_merge($this->draftAnswers($lead), [
                    'name'  => $lead->name,
                    'email' => $lead->email,
                    // Skipping ahead to the picker only makes sense if the
                    // steps being skipped are actually answered. A lead from
                    // before the questionnaire existed still gets their name
                    // and email filled in, just from step 1.
                    'resume' => $this->questionnaireComplete($lead),
                ]);
            }
        }

        $user = auth()->user();

        if (!$user) {
            return null;
        }

        $lead = \App\Models\HighTicketLead::where('email', $user->email)
            ->where('course_id', $course->id)
            ->latest('id')
            ->first();

        return $lead ? $this->draftAnswers($lead) : null;
    }

    /** Every field the wizard's step 1 marks required (`social_url` is optional). */
    private function questionnaireComplete(\App\Models\HighTicketLead $lead): bool
    {
        foreach (['phone', 'occupation', 'bottleneck', 'expertise'] as $field) {
            if (blank($lead->{$field})) {
                return false;
            }
        }

        return true;
    }

    /** @return array<string, ?string> */
    private function draftAnswers(\App\Models\HighTicketLead $lead): array
    {
        return [
            'phone'      => $lead->phone,
            'occupation' => $lead->occupation,
            'bottleneck' => $lead->bottleneck,
            'expertise'  => $lead->expertise,
            'social_url' => $lead->social_url,
            // So a resumed draft still carries a verdict the submit can re-score
            // (011 FR-129); null on any lead that predates the screening.
            ...$lead->only(\App\Support\BookingScreening::fields()),
            // One bit, not the rubric: whether these stored answers still pass.
            // The wizard skips step 1 on the strength of it (FR-137), so it has
            // to be decided here — a declined lead reloading the page would
            // otherwise walk past the gate on its own prefilled answers.
            'screening_cleared' => $lead->screened_at !== null
                && \App\Support\BookingScreening::passes($lead->only(\App\Support\BookingScreening::fields())),
            // Only ever stored when it was valid, so restoring it cannot
            // resurrect a dead code — and dropping it would quietly shorten a
            // 45-minute consultation the applicant already qualified for.
            'code'       => $lead->booking_code,
        ];
    }

    /**
     * Traffic source capture moved to the site-wide TrackTrafficSource
     * middleware (002 US10); only the coupon capture stays page-specific.
     */
    private function captureTrafficSource(Request $request): void
    {
        // Capture shareable discount coupon (?coupon=CODE) alongside traffic attribution (US5).
        $couponParam = $request->query('coupon');
        if (is_string($couponParam) && trim($couponParam) !== '') {
            $code = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $couponParam), 0, 6));
            if ($code !== '') {
                $request->session()->put('checkout_coupon', $code);
            }
        }
    }
}
