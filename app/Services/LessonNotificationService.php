<?php

namespace App\Services;

use App\Mail\LessonAddedNotification;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Purchase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * 004 US6 — single source of truth for "who gets told about a new lesson"
 * (FR-024).
 *
 * The recipient rule lives here and nowhere else: LessonController@store,
 * LessonController@update and ChapterController@index (for the preview count)
 * all go through it. Duplicating the query is how you end up with somebody
 * getting two mails and somebody else getting none, with nothing in the logs.
 */
class LessonNotificationService
{
    /**
     * Holders who may be told about this lesson: paid, not refunded, not a
     * course owner's own system_assigned row, and — on a multi-plan course —
     * only if their tier actually includes the lesson. A plan holder must not
     * be pointed at content the classroom hides outright (011 FR-095).
     */
    public function recipients(Lesson $lesson): Collection
    {
        return Purchase::where('course_id', $lesson->course_id)
            ->where('status', '!=', 'refunded')
            ->where('type', '!=', 'system_assigned')
            ->with(['user', 'plan.lessons:id'])
            ->get()
            ->filter(function (Purchase $purchase) use ($lesson) {
                $lessonIds = $purchase->accessibleLessonIds();

                return $lessonIds === null || in_array($lesson->id, $lessonIds);
            })
            ->filter(fn (Purchase $purchase) => (bool) $purchase->user?->email)
            ->values();
    }

    /**
     * Send the notification one by one (D3) and stamp notified_at.
     *
     * A single failure is logged and skipped so the remaining recipients still
     * get their mail and the lesson still saves. Returns the counts the flash
     * message needs (FR-026) rather than a bare int, so the caller can tell
     * "nobody qualified" apart from "everybody bounced".
     *
     * @return array{sent: int, failed: int, eligible: int}
     */
    public function notify(Lesson $lesson): array
    {
        $recipients = $this->recipients($lesson);
        $sent = 0;
        $failed = 0;

        foreach ($recipients as $purchase) {
            try {
                Mail::to($purchase->user->email)
                    ->send(new LessonAddedNotification($lesson->course, $lesson));
                $sent++;
            } catch (\Exception $e) {
                $failed++;
                Log::error('Failed to send lesson notification', [
                    'purchase_id' => $purchase->id,
                    'lesson_id' => $lesson->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($sent > 0) {
            // notified_at is a side effect of sending, not a form field (D27),
            // so it is written here and kept out of $fillable.
            $lesson->forceFill(['notified_at' => now()])->save();
        }

        Log::info('Lesson notification dispatched', [
            'lesson_id' => $lesson->id,
            'course_id' => $lesson->course_id,
            'sent' => $sent,
            'failed' => $failed,
        ]);

        return ['sent' => $sent, 'failed' => $failed, 'eligible' => $recipients->count()];
    }

    /**
     * Eligible holder counts for the course, bucketed by plan, so the lesson
     * form can show "預計通知 N 位" without a query per lesson (FR-027). The
     * frontend adds no_plan to the buckets of the lesson's own plans.
     *
     * @return array{no_plan: int, plans: array<int, int>}
     */
    public function notifiableCounts(Course $course): array
    {
        $rows = Purchase::where('course_id', $course->id)
            ->where('status', '!=', 'refunded')
            ->where('type', '!=', 'system_assigned')
            ->whereHas('user')
            ->selectRaw('course_plan_id, COUNT(*) as total')
            ->groupBy('course_plan_id')
            ->pluck('total', 'course_plan_id');

        return [
            'no_plan' => (int) ($rows[''] ?? $rows[null] ?? 0),
            'plans' => $rows->filter(fn ($total, $planId) => $planId !== null && $planId !== '')
                ->mapWithKeys(fn ($total, $planId) => [(int) $planId => (int) $total])
                ->all(),
        ];
    }

    /**
     * Why a send would reach nobody, for the flash message and the form hint
     * (FR-026). Null when there is at least one recipient.
     */
    public function emptyReason(Lesson $lesson): ?string
    {
        if ($this->recipients($lesson)->isNotEmpty()) {
            return null;
        }

        $counts = $this->notifiableCounts($lesson->course);
        $hasHolders = $counts['no_plan'] > 0 || array_sum($counts['plans']) > 0;

        return $hasHolders
            ? '此小節尚未歸屬任何方案，綁定方案的學員看不到它'
            : '此課程尚無可通知的學員';
    }
}
