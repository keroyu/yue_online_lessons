<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 011 US43 / FR-260 — per-course body for the screening refusal email.
 * Null means the site-wide `high_ticket_screening_declined` template body.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->text('high_ticket_decline_email_md')->nullable()->after('high_ticket_decline_html');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('high_ticket_decline_email_md');
        });
    }
};
