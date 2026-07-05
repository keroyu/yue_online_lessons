<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * One-off backfill for the points system going live:
 *  1. Generate a permanent referral_code for every existing user.
 *  2. Activate referral for users whose lifetime paid purchases already meet the threshold.
 *  3. Backfill earn_homework ledger rows for existing assignment completions.
 *     (Points are ALREADY in users.points from the old increment — do NOT re-increment;
 *      this only makes the ledger consistent so reconcile passes.)
 */
return new class extends Migration
{
    public function up(): void
    {
        // Existing codes to avoid collisions (should be none, but be safe).
        $used = DB::table('users')->whereNotNull('referral_code')->pluck('referral_code')->flip();

        $genCode = function () use (&$used) {
            $charset = str_split('23456789ABCDEFGHJKMNPQRSTUVWXYZ');
            do {
                $code = '';
                for ($i = 0; $i < 8; $i++) {
                    $code .= $charset[array_rand($charset)];
                }
            } while (isset($used[$code]));
            $used[$code] = true;

            return $code;
        };

        $threshold = (int) (DB::table('site_settings')
            ->where('key', 'referral_threshold_amount')->value('value') ?? 3000);

        DB::table('users')->orderBy('id')->each(function ($u) use ($genCode, $threshold) {
            $updates = [];

            if (empty($u->referral_code)) {
                $updates['referral_code'] = $genCode();
            }

            if (empty($u->referral_activated_at)) {
                $paid = (int) DB::table('purchases')
                    ->where('user_id', $u->id)
                    ->where('type', 'paid')
                    ->sum('amount');
                if ($paid >= $threshold) {
                    $updates['referral_activated_at'] = now();
                }
            }

            if ($updates) {
                DB::table('users')->where('id', $u->id)->update($updates);
            }
        });

        // Backfill homework ledger. Historical completions were awarded a flat 100.
        DB::table('assignment_completions')->orderBy('id')->each(function ($c) {
            $exists = DB::table('point_transactions')
                ->where('user_id', $c->user_id)
                ->where('type', 'earn_homework')
                ->where('reference_type', 'assignment')
                ->where('reference_id', $c->assignment_id)
                ->exists();

            if (! $exists) {
                DB::table('point_transactions')->insert([
                    'user_id'        => $c->user_id,
                    'amount'         => 100,
                    'type'           => 'earn_homework',
                    'reference_type' => 'assignment',
                    'reference_id'   => $c->assignment_id,
                    'note'           => null,
                    'available_at'   => $c->created_at,
                    'created_at'     => $c->created_at,
                    'matured_synced' => true,
                ]);
            }
        });
    }

    public function down(): void
    {
        // Data backfill — reverse the ledger rows we inserted; leave referral codes.
        DB::table('point_transactions')
            ->where('type', 'earn_homework')
            ->where('reference_type', 'assignment')
            ->delete();
    }
};
