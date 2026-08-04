<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Re-bucket the rows the fbclid-means-paid rule mis-filed (002 FR-024).
     *
     * Under the old rule `(channel=paid, source=facebook)` was reachable ONLY
     * through the fbclid branch — utm_source=facebook resolved to social, and
     * gclid/ttclid carried their own platforms. So every such row is known to
     * be a Meta click wrongly booked as an ad, not a guess.
     *
     * Where those clicks actually came from is NOT recoverable: the daily
     * aggregate keeps no fbclid or referrer, only the resolved pair. They are
     * therefore collapsed into social/meta — "came from Meta, surface unknown"
     * — rather than inventing an Instagram/Facebook split after the fact.
     *
     * Rows are merged rather than updated in place: (course_id, date, channel,
     * source) is unique, so a same-day social/meta row must absorb the counters.
     */
    private const FROM = ['channel' => 'paid', 'source' => 'facebook'];
    private const TO   = ['channel' => 'social', 'source' => 'meta'];

    private const COUNTERS = ['views', 'add_to_cart', 'checkouts', 'purchases', 'revenue'];

    public function up(): void
    {
        $this->move(self::FROM, self::TO);
    }

    /** Reversible for a rollback, though the original pair was the wrong answer. */
    public function down(): void
    {
        $this->move(self::TO, self::FROM);
    }

    private function move(array $from, array $to): void
    {
        DB::transaction(function () use ($from, $to) {
            $rows = DB::table('course_daily_stats')
                ->where('channel', $from['channel'])
                ->where('source', $from['source'])
                ->get();

            foreach ($rows as $row) {
                $target = DB::table('course_daily_stats')
                    ->where('course_id', $row->course_id)
                    ->where('date', $row->date)
                    ->where('channel', $to['channel'])
                    ->where('source', $to['source'])
                    ->first();

                if ($target) {
                    $increments = [];
                    foreach (self::COUNTERS as $column) {
                        $increments[$column] = DB::raw("{$column} + {$row->$column}");
                    }
                    DB::table('course_daily_stats')->where('id', $target->id)->update($increments);
                    DB::table('course_daily_stats')->where('id', $row->id)->delete();

                    continue;
                }

                DB::table('course_daily_stats')->where('id', $row->id)->update([
                    'channel' => $to['channel'],
                    'source'  => $to['source'],
                ]);
            }
        });
    }
};
