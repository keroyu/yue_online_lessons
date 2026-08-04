<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A permanent deep link back into the wizard's slot picker (011 FR-042).
     *
     * Distinct from `confirm_token`, which is single-purpose and expires in an
     * hour: a waitlisted applicant may wait weeks for the consultant to open
     * availability, and must not have to retype the questionnaire when the
     * 「通知新時段」 mail finally arrives.
     */
    public function up(): void
    {
        Schema::table('high_ticket_leads', function (Blueprint $table) {
            $table->char('resume_token', 64)->nullable()->after('confirmed_at');
            $table->unique('resume_token', 'uniq_resume_token');
        });
    }

    public function down(): void
    {
        Schema::table('high_ticket_leads', function (Blueprint $table) {
            $table->dropUnique('uniq_resume_token');
            $table->dropColumn('resume_token');
        });
    }
};
