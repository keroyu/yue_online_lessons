<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The follow-up email gets its own columns (011 US35 / FR-186).
 *
 * It used to be the eighth section of `summary`, which meant it shared that
 * column's edit lock: an admin who polished the letter and then pressed
 * "regenerate summary" lost it, with nothing on screen to say so. Two readers
 * (the consultant, the customer) and two lifecycles want two columns.
 *
 * Plain text, not Markdown — this is a thing to paste into a mail client, not a
 * document to render, so it does not take the `*_md` suffix.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultation_notes', function (Blueprint $table) {
            $table->text('followup_email')->nullable()->after('summary_edited_at');
            $table->timestamp('followup_email_generated_at')->nullable()->after('followup_email');
            $table->timestamp('followup_email_edited_at')->nullable()->after('followup_email_generated_at');
        });
    }

    public function down(): void
    {
        Schema::table('consultation_notes', function (Blueprint $table) {
            $table->dropColumn([
                'followup_email',
                'followup_email_generated_at',
                'followup_email_edited_at',
            ]);
        });
    }
};
