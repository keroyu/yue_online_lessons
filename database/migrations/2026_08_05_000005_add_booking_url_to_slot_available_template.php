<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Put the resume link into the live 「新時段通知」 template (011 FR-042).
     *
     * The seeder only ever runs on a fresh install, and it uses
     * updateOrCreate — re-running it on production would flatten whatever the
     * admin has since written. So this appends the new `{{booking_url}}` line
     * instead of replacing the body, and skips any template that already
     * mentions the variable. Without it the feature ships dead: the mail goes
     * out with no link and the waitlisted applicant still starts from step 1.
     */
    public function up(): void
    {
        $template = DB::table('email_templates')
            ->where('event_type', 'high_ticket_slot_available')
            ->first();

        if (!$template || str_contains((string) $template->body_md, '{{booking_url}}')) {
            return;
        }

        $addition = $template->body_type === 'html'
            ? "\n<p>直接從這裡選定時間，先前填寫的資料都還在，不需要重填：<br><a href=\"{{booking_url}}\">{{booking_url}}</a></p>"
            : "\n\n直接從這裡選定時間，先前填寫的資料都還在，不需要重填：\n\n{{booking_url}}";

        DB::table('email_templates')
            ->where('id', $template->id)
            ->update(['body_md' => $template->body_md . $addition]);
    }

    public function down(): void
    {
        // Appending text is not worth un-appending — the admin may well have
        // edited around it by then, and a stray paragraph breaks nothing.
    }
};
