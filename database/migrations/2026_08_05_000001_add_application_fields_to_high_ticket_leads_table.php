<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Application questionnaire, commitment timestamp, email-confirmation and
     * Zoom columns for the multi-step booking flow (011 US9–US12).
     *
     * Every column is nullable: rows created by the old one-step form have none
     * of these values and must keep working in the admin list.
     */
    public function up(): void
    {
        Schema::table('high_ticket_leads', function (Blueprint $table) {
            // US9 — application questionnaire
            $table->string('phone', 30)->nullable()->after('email');
            $table->string('occupation', 255)->nullable()->after('phone');
            $table->text('bottleneck')->nullable()->after('occupation');
            $table->text('expertise')->nullable()->after('bottleneck');
            $table->string('social_url', 500)->nullable()->after('expertise');
            $table->timestamp('commitments_accepted_at')->nullable()->after('social_url');

            // US10 — booking bonus code that extended the consultation
            $table->string('booking_code', 50)->nullable()->after('commitments_accepted_at');

            // US11 — email confirmation; confirm_expires_at doubles as the slot hold deadline
            $table->char('confirm_token', 64)->nullable()->unique('uniq_confirm_token');
            $table->timestamp('confirm_expires_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();

            // US12 — Zoom meeting created after confirmation
            $table->string('zoom_meeting_id', 50)->nullable();
            $table->string('zoom_join_url', 500)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('high_ticket_leads', function (Blueprint $table) {
            $table->dropUnique('uniq_confirm_token');
            $table->dropColumn([
                'phone',
                'occupation',
                'bottleneck',
                'expertise',
                'social_url',
                'commitments_accepted_at',
                'booking_code',
                'confirm_token',
                'confirm_expires_at',
                'confirmed_at',
                'zoom_meeting_id',
                'zoom_join_url',
            ]);
        });
    }
};
