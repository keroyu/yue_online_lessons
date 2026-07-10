<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // enum → varchar so category slugs become admin-editable.
        // MySQL only: sqlite (test DB) is dynamically typed and already stores the
        // column as TEXT, so the raw MODIFY is unnecessary and would be invalid syntax.
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE courses MODIFY content_category VARCHAR(50) NOT NULL DEFAULT 'monetization'");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE courses MODIFY content_category ENUM('mindset','finance','monetization') NOT NULL DEFAULT 'monetization'");
    }
};
