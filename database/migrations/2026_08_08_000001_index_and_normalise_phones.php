<?php

use App\Support\PhoneNumber;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Make the phone column usable as a deduplication key (011 US16 / FR-064).
 *
 * Two jobs: an index, because the column is now read on every application; and
 * a one-off rewrite of the values already there, because `0912-345-678` and
 * `0912345678` are the same person and only one of those forms will ever match
 * what the normalised input produces from here on.
 *
 * `orders.buyer_phone` is deliberately untouched (D62): it is a snapshot of a
 * settled transaction used for contact and receipts, not a lookup key, and
 * rewriting historical order records carries risk with no matching benefit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('high_ticket_leads', function (Blueprint $table) {
            $table->index('phone', 'idx_high_ticket_leads_phone');
        });

        $this->normaliseColumn('high_ticket_leads', 'phone');
        $this->normaliseColumn('users', 'phone');
    }

    public function down(): void
    {
        // The rewrite is not reversible — the original spellings are gone and
        // there is nothing to reconstruct them from. Only the index comes back.
        Schema::table('high_ticket_leads', function (Blueprint $table) {
            $table->dropIndex('idx_high_ticket_leads_phone');
        });
    }

    /**
     * Chunked and only writing rows that actually change: on a table of any
     * size a blanket UPDATE would touch every row to leave most of them
     * identical.
     */
    private function normaliseColumn(string $table, string $column): void
    {
        DB::table($table)
            ->whereNotNull($column)
            ->where($column, '<>', '')
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($table, $column) {
                foreach ($rows as $row) {
                    $normalised = PhoneNumber::normalise($row->{$column});

                    if ($normalised === $row->{$column}) {
                        continue;
                    }

                    DB::table($table)->where('id', $row->id)->update([$column => $normalised]);
                }
            });
    }
};
