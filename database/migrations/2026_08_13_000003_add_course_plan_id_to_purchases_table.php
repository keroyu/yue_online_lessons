<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which plan this entitlement covers. NULL means the whole course (FR-087) —
 * that is what keeps every pre-existing purchase and every path without a plan
 * picker (checkout, gifting, member import, point redemption) correct with no
 * change at all.
 *
 * restrictOnDelete is deliberate: a plan somebody still holds must not be
 * deletable. nullOnDelete would silently promote those members to full access,
 * which is the worst possible failure mode here — quiet, invisible, and in the
 * direction of leaking content (FR-093).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->foreignId('course_plan_id')
                ->nullable()
                ->after('course_id')
                ->constrained()
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropForeign(['course_plan_id']);
            $table->dropColumn('course_plan_id');
        });
    }
};
