<?php

namespace App\Jobs;

use App\Mail\BatchEmailMail;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendBatchEmailJob implements ShouldQueue
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
     * @param array $memberIds Array of member IDs to send email to
     * @param string $subject Email subject
     * @param string $body Email body content
     */
    public function __construct(
        public array $memberIds,
        public string $subject,
        public string $body
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $members = User::whereIn('id', $this->memberIds)
            ->where('role', 'member')
            ->whereNotNull('email')
            ->get();

        foreach ($members as $member) {
            try {
                Mail::to($member->email)
                    ->send(new BatchEmailMail($this->subject, $this->body));
            } catch (\Exception $e) {
                Log::error('Failed to send batch email', [
                    'member_id' => $member->id,
                    'email' => $member->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
