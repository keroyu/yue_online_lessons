<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('broadcasts', function (Blueprint $table) {
            $table->timestamp('scheduled_at')->nullable()->after('status');
            $table->index('scheduled_at');
        });

        // The dev MySQL DB created status as an enum before this feature landed; widen it
        // to include 'scheduled'. Fresh installs create status as a plain string already.
        if (DB::getDriverName() === 'mysql') {
            $type = DB::selectOne("SHOW COLUMNS FROM broadcasts WHERE Field = 'status'");
            if ($type && str_starts_with(strtolower($type->Type ?? ''), 'enum')) {
                DB::statement("ALTER TABLE broadcasts MODIFY status ENUM('draft','scheduled','sending','sent') NOT NULL DEFAULT 'draft'");
            }
        }
    }

    public function down(): void
    {
        Schema::table('broadcasts', function (Blueprint $table) {
            $table->dropIndex(['scheduled_at']);
            $table->dropColumn('scheduled_at');
        });
    }
};
