<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateTestimonialRequest;
use App\Models\HighTicketLead;
use App\Models\Testimonial;
use App\Services\TestimonialService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

/**
 * Testimonial link and review, from the leads list (011 US40). Staff-level,
 * same audience as the list itself.
 */
class TestimonialController extends Controller
{
    public function __construct(private TestimonialService $testimonialService) {}

    public function link(HighTicketLead $lead): JsonResponse
    {
        return response()->json(['url' => $this->testimonialService->issueLink($lead)]);
    }

    public function update(UpdateTestimonialRequest $request, Testimonial $testimonial): RedirectResponse
    {
        $data = $request->validated();
        $this->testimonialService->update($testimonial, $data);

        $message = match ($data['status'] ?? null) {
            'published' => '評語已顯示於銷售頁',
            'hidden'    => '評語已隱藏',
            default     => '身份已改為「' . $testimonial->identity_label . '」',
        };

        return back()->with('success', $message);
    }

    public function destroy(Testimonial $testimonial): RedirectResponse
    {
        $this->testimonialService->delete($testimonial);

        return back()->with('success', '評語已刪除（已發出的積分不收回）');
    }
}
