<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The transcript is download-only now (011 FR-120), so nothing edits it and
 * nothing writes this column.
 *
 * Dropping rather than leaving it: a permanently-null timestamp is an invitation
 * to write code that checks it. No data is lost — the admin transcript editor
 * existed for a matter of hours and never set it on production.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultation_notes', function (Blueprint $table) {
            $table->dropColumn('transcript_edited_at');
        });
    }

    public function down(): void
    {
        Schema::table('consultation_notes', function (Blueprint $table) {
            $table->timestamp('transcript_edited_at')->nullable()->after('transcript_fetched_at');
        });
    }
};
