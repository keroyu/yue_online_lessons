<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE courses MODIFY COLUMN type ENUM('lecture', 'mini', 'full', 'high_ticket') NOT NULL");
            if (!Schema::hasColumn('courses', 'high_ticket_hide_price')) {
                DB::statement("ALTER TABLE courses ADD COLUMN high_ticket_hide_price TINYINT(1) NOT NULL DEFAULT 0 AFTER type");
            }
        } else {
            // SQLite: add column only (no ENUM support, type column already exists)
            if (!Schema::hasColumn('courses', 'high_ticket_hide_price')) {
                Schema::table('courses', function (Blueprint $table) {
                    $table->boolean('high_ticket_hide_price')->default(false)->after('type');
                });
            }
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            if (Schema::hasColumn('courses', 'high_ticket_hide_price')) {
                DB::statement("ALTER TABLE courses DROP COLUMN high_ticket_hide_price");
            }
            DB::statement("ALTER TABLE courses MODIFY COLUMN type ENUM('lecture', 'mini', 'full') NOT NULL");
        } else {
            Schema::table('courses', function (Blueprint $table) {
                $table->dropColumn('high_ticket_hide_price');
            });
        }
    }
};
