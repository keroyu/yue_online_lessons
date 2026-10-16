<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTestimonialRequest;
use App\Models\HighTicketLead;
use App\Models\Testimonial;
use App\Services\TestimonialService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The public testimonial form behind the link in the follow-up email
 * (011 US40). The token is the credential — no login (D159).
 */
class TestimonialController extends Controller
{
    public function __construct(private TestimonialService $testimonialService) {}

    public function create(string $token): Response
    {
        $lead = $this->leadFor($token);
        $lead->load(['course:id,name,slug', 'testimonial:id,lead_id,reward_points,identity']);

        $consultedAt = $lead->testimonial ? null : $this->testimonialService->consultedAt($lead);

        // A link whose booking was cancelled after it was sent has nothing left
        // to vouch for.
        abort_if(!$lead->testimonial && $consultedAt === null, 404);

        $identity = $lead->testimonial?->identity ?? $this->testimonialService->identityFor($lead);

        // Someone who has not bought yet gets other clients' words and a way
        // back to the course, here and on the thank-you page (FR-251). A
        // student already bought — sales material would be noise.
        $isProspect = $identity === 'consultation' && $lead->course !== null;

        return Inertia::render('Testimonial/Create', [
            'course_name'      => $lead->course?->name,
            // Identity replaces the consultation time on this page (FR-247).
            'identity_label'   => Testimonial::IDENTITY_LABELS[$identity],
            'testimonials'     => $isProspect
                ? $this->testimonialService->publishedFor($lead->course_id, $lead->testimonial?->id)
                : [],
            'course_url'       => $isProspect ? route('course.show', $lead->course) : null,
            'default_nickname' => $lead->name,
            'submitted'        => $lead->testimonial !== null,
            'reward_points'    => $this->testimonialService->rewardPoints(),
            'awarded_points'   => $lead->testimonial?->reward_points,
        ]);
    }

    public function store(StoreTestimonialRequest $request, string $token): RedirectResponse
    {
        $lead = $this->leadFor($token);

        $this->testimonialService->submit($lead, $request->validated());

        return redirect()->route('testimonials.create', $token);
    }

    private function leadFor(string $token): HighTicketLead
    {
        return HighTicketLead::where('testimonial_token', $token)->firstOrFail();
    }
}
