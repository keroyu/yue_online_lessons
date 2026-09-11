<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-row show/hide for the homepage's featured courses (002 US19 / FR-049).
 *
 * No index and no backfill: this is a hand-maintained list of a handful of rows
 * that the front end reads whole and sorts, so an index would only cost writes;
 * and the default already reproduces the old behaviour for every existing row,
 * which is what a backfill would have written.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('homepage_featured_courses', function (Blueprint $table) {
            $table->boolean('is_visible')->default(true)->after('blurb');
        });
    }

    public function down(): void
    {
        Schema::table('homepage_featured_courses', function (Blueprint $table) {
            $table->dropColumn('is_visible');
        });
    }
};
