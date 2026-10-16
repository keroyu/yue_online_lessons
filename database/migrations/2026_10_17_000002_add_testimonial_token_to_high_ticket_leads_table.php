<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 011 US40 — the testimonial link's credential. Issued lazily on the first
 * copy (D162), so null means "never invited".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('high_ticket_leads', function (Blueprint $table) {
            $table->string('testimonial_token', 64)->nullable()->unique()->after('resume_token');
        });
    }

    public function down(): void
    {
        Schema::table('high_ticket_leads', function (Blueprint $table) {
            $table->dropUnique(['testimonial_token']);
            $table->dropColumn('testimonial_token');
        });
    }
};
