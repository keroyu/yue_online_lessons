<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add 'ebook' as a product category. Schema::change() with the full value
        // list applies to both engines — 2026_04_09_000001 added 'high_ticket'
        // through a MySQL-only DB::statement, so the sqlite test database was
        // left on three values and could never persist a high-ticket course.
        // Restating every value here fixes that gap too (004 D10).
        Schema::table('courses', function (Blueprint $table) {
            $table->enum('type', ['lecture', 'mini', 'full', 'high_ticket', 'ebook'])->change();
        });
    }

    public function down(): void
    {
        // Callers must ensure no 'ebook' rows remain before rolling back.
        Schema::table('courses', function (Blueprint $table) {
            $table->enum('type', ['lecture', 'mini', 'full', 'high_ticket'])->change();
        });
    }
};
