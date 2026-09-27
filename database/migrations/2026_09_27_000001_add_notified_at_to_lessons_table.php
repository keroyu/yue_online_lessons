<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 004 US6 — when the lesson-added notification last went out.
     *
     * Deliberately not back-filled: guessing a timestamp for lessons mailed
     * before this column existed would be worse than null, whose meaning
     * ("no send on record") already covers them.
     */
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dateTime('notified_at')->nullable()->after('is_preview');
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn('notified_at');
        });
    }
};
