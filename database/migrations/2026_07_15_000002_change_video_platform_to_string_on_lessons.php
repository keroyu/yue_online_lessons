<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // enum → varchar so new video sources (cloudflare, …) don't require schema
        // changes. Schema::change() (not raw MODIFY) so the sqlite test DB also
        // drops its enum CHECK constraint.
        Schema::table('lessons', function (Blueprint $table) {
            $table->string('video_platform', 20)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->enum('video_platform', ['vimeo', 'youtube'])->nullable()->change();
        });
    }
};
