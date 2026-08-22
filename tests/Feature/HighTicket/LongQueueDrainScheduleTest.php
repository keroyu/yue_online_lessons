<?php

namespace Tests\Feature\HighTicket;

use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

/**
 * 011 US23 — the `long` queue must have a consumer that nobody has to remember.
 *
 * `ProcessZoomTranscriptJob` is the only job on the `database_long` connection,
 * and the site's Forge worker consumes the `default` queue alone. Twice the gap
 * was closed by starting a worker by hand, and twice the transcripts stopped
 * without a single error — the jobs simply sat unreserved. This test is what
 * makes the third time impossible: the drain lives in the scheduler, so deleting
 * it is a code change and shows up here.
 */
class LongQueueDrainScheduleTest extends TestCase
{
    public function test_scheduler_drains_the_long_queue(): void
    {
        $commands = collect(app(Schedule::class)->events())
            ->map(fn ($event) => $event->command ?? '')
            ->filter(fn (string $command) => str_contains($command, 'queue:work'));

        $this->assertCount(1, $commands, 'the long queue drain should be scheduled exactly once');

        $command = $commands->first();

        $this->assertStringContainsString('database_long', $command);
        $this->assertStringContainsString('--queue=long', $command);
        // Without this the drain never exits and every later tick is skipped by
        // the overlap lock — a daemon by accident, with none of a daemon's
        // supervision.
        $this->assertStringContainsString('--stop-when-empty', $command);
    }
}
