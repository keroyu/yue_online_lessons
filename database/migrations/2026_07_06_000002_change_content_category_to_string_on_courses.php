<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // enum → varchar so category slugs become admin-editable
        DB::statement("ALTER TABLE courses MODIFY content_category VARCHAR(50) NOT NULL DEFAULT 'monetization'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE courses MODIFY content_category ENUM('mindset','finance','monetization') NOT NULL DEFAULT 'monetization'");
    }
};
