<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultation_slots', function (Blueprint $table) {
            // Which staff member this stretch of time belongs to (011 US15 /
            // FR-060). No foreign key, matching how `lead_id` is handled on this
            // table — a deleted user should not take the schedule down with it.
            //
            // Note what this is NOT: `starts_at` is still globally unique, so
            // this marks ownership on one shared calendar rather than giving
            // each consultant their own. Two consultants cannot open the same
            // 15-minute unit (D57).
            $table->unsignedBigInteger('consultant_id')->nullable()->after('lead_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('consultation_slots', function (Blueprint $table) {
            $table->dropColumn('consultant_id');
        });
    }
};
