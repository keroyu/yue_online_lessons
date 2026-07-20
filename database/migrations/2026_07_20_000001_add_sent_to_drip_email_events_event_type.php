<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add 'sent' event type: the created_at of a 'sent' event is the actual
        // moment a lesson email left the queue, used as the anchor for the video
        // free-viewing window. Schema::change() (not raw MODIFY) so the sqlite
        // test DB updates its CHECK constraint too.
        Schema::table('drip_email_events', function (Blueprint $table) {
            $table->enum('event_type', ['opened', 'clicked', 'sent'])->change();
        });
    }

    public function down(): void
    {
        // Revert to the original two values. Callers must ensure no 'sent' rows
        // remain before rolling back, or the CHECK/enum constraint will reject them.
        Schema::table('drip_email_events', function (Blueprint $table) {
            $table->enum('event_type', ['opened', 'clicked'])->change();
        });
    }
};
