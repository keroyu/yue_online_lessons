<?php

namespace App\Console\Commands;

use App\Models\Course;
use Illuminate\Console\Command;

class UpdateCourseStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'courses:update-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update preorder courses to selling when sale_at time is reached';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $updated = Course::where('status', 'preorder')
            ->whereNotNull('sale_at')
            ->where('sale_at', '<=', now())
            ->update(['status' => 'selling']);

        if ($updated > 0) {
            $this->info("Updated {$updated} course(s) from preorder to selling.");
        }

        return Command::SUCCESS;
    }
}
