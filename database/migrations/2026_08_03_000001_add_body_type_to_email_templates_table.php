<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_templates', function (Blueprint $table) {
            // 'markdown' | 'html' — string rather than enum so adding a format
            // later is a code change, not a migration (004 does the same).
            $table->string('body_type', 10)->default('markdown')->after('subject');
        });
    }

    public function down(): void
    {
        Schema::table('email_templates', function (Blueprint $table) {
            $table->dropColumn('body_type');
        });
    }
};
