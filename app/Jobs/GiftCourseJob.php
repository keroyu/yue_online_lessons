<?php

namespace App\Jobs;

use App\Mail\CourseGiftedMail;
use App\Models\Course;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class GiftCourseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * Create a new job instance.
     *
     * @param array $memberIds Array of member IDs to gift course to
     * @param int $courseId Course ID to gift
     */
    public function __construct(
        public array $memberIds,
        public int $courseId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $course = Course::find($this->courseId);

        if (!$course) {
            Log::error('Gift course job failed: Course not found', [
                'course_id' => $this->courseId,
            ]);
            return;
        }

        $courseDescription = $course->description ?: '（無課程簡介）';

        $members = User::whereIn('id', $this->memberIds)
            ->where('role', 'member')
            ->get();

        foreach ($members as $member) {
            try {
                // Skip if member already owns the course
                if ($member->purchases()->where('course_id', $this->courseId)->exists()) {
                    Log::info('Gift course skipped: Member already owns course', [
                        'member_id' => $member->id,
                        'course_id' => $this->courseId,
                    ]);
                    continue;
                }

                // Create gift purchase
                Purchase::create([
                    'user_id' => $member->id,
                    'course_id' => $this->courseId,
                    'buyer_email' => $member->email ?? '',
                    'amount' => 0,
                    'currency' => 'TWD',
                    'status' => 'paid',
                    'type' => 'gift',
                ]);

                // Send notification email if member has email
                if ($member->email) {
                    Mail::to($member->email)
                        ->send(new CourseGiftedMail($course->name, $courseDescription));
                }
            } catch (\Exception $e) {
                Log::error('Failed to gift course to member', [
                    'member_id' => $member->id,
                    'course_id' => $this->courseId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
