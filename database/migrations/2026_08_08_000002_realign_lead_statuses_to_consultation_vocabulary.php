<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Re-file historical leads under the consultation vocabulary (011 FR-055).
 *
 * The display names changed meaning, the stored values did not:
 *
 *   contacted    was 已聯繫（我聯絡過他）  → now reads 已面談（面談發生了）
 *   no_response  was 未回應（他沒回我）    → now reads 未出席（他沒出現）
 *
 * Every row written before this deploy carries the old meaning, so leaving them
 * alone would silently promote "I sent them a message" to "we had the
 * consultation" — and mark people who simply never replied as no-shows, which
 * US16 then uses to block them from ever booking.
 *
 *   contacted   → pending    （待面談：聯絡過但面談還沒發生）
 *   no_response → cancelled  （已取消：從來沒談成，而且該讓他們能重新預約）
 *
 * `pending`, `converted` and `closed` keep both their value and their meaning.
 *
 * `cancelled_at` is deliberately left null: we do not know when these went
 * cold, and inventing a timestamp would be worse than an absent one. Nothing
 * reads it for these rows — every historical lead predates `confirmed_at` and
 * the `consultation_slots` table, so none of them holds a booking.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Runs once, at the deploy that ships the rename — so "every row that
        // exists right now" is exactly "every row with the old meaning".
        DB::table('high_ticket_leads')->where('status', 'contacted')->update(['status' => 'pending']);
        DB::table('high_ticket_leads')->where('status', 'no_response')->update(['status' => 'cancelled']);
    }

    public function down(): void
    {
        // Not reversible: after this runs, a `pending` row could be either a
        // converted 已聯繫 or a genuine 待面談, and nothing distinguishes them.
    }
};
