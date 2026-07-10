<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('social_links')
            ->where('platform', 'substack')
            ->update(['platform' => 'blog']);
    }

    public function down(): void
    {
        DB::table('social_links')
            ->where('platform', 'blog')
            ->update(['platform' => 'substack']);
    }
};
