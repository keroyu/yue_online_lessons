<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Services\DripService;
use Illuminate\Http\JsonResponse;

/**
 * 010 US17 — what this lesson's letter looks like when it goes out.
 *
 * Renders the real DripLessonMail rather than re-deriving the HTML, because the
 * delivered mail is several transforms away from `md_content` and those
 * transforms are exactly what the admin is here to check (FR-030).
 */
class DripLessonPreviewController extends Controller
{
    public function __invoke(Lesson $lesson, DripService $drip): JsonResponse
    {
        $lesson->loadMissing('course');

        // A standard course never sends this letter; previewing one would only
        // suggest otherwise (FR-033).
        abort_unless($lesson->course?->course_type === 'drip', 404);

        // No subscription and no user: placeholder greeting, dead unsubscribe
        // link, no tracking pixel (FR-032).
        $mail = $drip->buildLessonMail($lesson);

        return response()->json([
            'subject' => $mail->envelope()->subject,
            'html'    => $mail->render(),
        ]);
    }
}
