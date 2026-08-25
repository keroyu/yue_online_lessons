<?php

namespace App\Http\Controllers;

use App\Models\DripEmailEvent;
use App\Models\DripSubscription;
use App\Models\Lesson;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class DripTrackingController extends Controller
{
    // 1x1 transparent GIF binary
    private const PIXEL = "\x47\x49\x46\x38\x39\x61\x01\x00\x01\x00\x80\x00\x00\xff\xff\xff\x00\x00\x00\x21\xf9\x04\x01\x00\x00\x00\x00\x2c\x00\x00\x00\x00\x01\x00\x01\x00\x00\x02\x02\x44\x01\x00\x3b";

    public function open(Request $request): Response
    {
        if ($request->hasValidSignature()) {
            $subId = $request->integer('sub');
            $lesId = $request->integer('les');

            try {
                DripEmailEvent::firstOrCreate(
                    [
                        'subscription_id' => $subId,
                        'lesson_id' => $lesId,
                        'event_type' => 'opened',
                    ],
                    [
                        'ip' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                    ]
                );
            } catch (\Exception $e) {
                Log::warning('Drip tracking open failed', [
                    'sub' => $subId,
                    'les' => $lesId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response(self::PIXEL, 200, [
            'Content-Type' => 'image/gif',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    public function click(Request $request): RedirectResponse
    {
        $lessonId = $request->integer('les');

        // The redirect target is always the lesson's own stored promo_url, never
        // the `url` query param: that param is client-supplied, and trusting it
        // would let anyone build a link through our domain to an arbitrary site
        // (open redirect). ClassroomController always sends the two in sync, so
        // legitimate use is unaffected.
        $lesson = $lessonId ? Lesson::find($lessonId) : null;
        $targetUrl = $lesson?->promo_url ?: '/';

        // Auth middleware guarantees a logged-in user; use session to identify subscriber.
        $user = $request->user();
        if ($user && $lessonId) {
            $subscription = DripSubscription::where('user_id', $user->id)
                ->whereHas('course.lessons', fn ($q) => $q->where('lessons.id', $lessonId))
                ->first();

            if ($subscription) {
                try {
                    DripEmailEvent::firstOrCreate(
                        [
                            'subscription_id' => $subscription->id,
                            'lesson_id' => $lessonId,
                            'event_type' => 'clicked',
                        ],
                        [
                            'target_url' => $targetUrl,
                            'ip' => $request->ip(),
                            'user_agent' => $request->userAgent(),
                        ]
                    );
                } catch (\Exception $e) {
                    Log::warning('Drip tracking click failed', [
                        'subscription_id' => $subscription->id,
                        'lesson_id' => $lessonId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return redirect()->away($targetUrl);
    }
}
