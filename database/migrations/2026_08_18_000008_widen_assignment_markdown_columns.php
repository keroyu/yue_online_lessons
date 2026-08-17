<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `text` cannot hold what the validator accepts (003 FR-016).
 *
 * MySQL's TEXT is 65,535 *bytes*; the rule is `max:50000` *characters*. In
 * Chinese that is three bytes each, so anything past roughly 21,845 characters
 * passes validation and then dies on INSERT with a 1406. A handout is exactly
 * the kind of field someone pastes a whole lecture into.
 *
 * longText matches what every other Markdown body on the site already uses
 * (`posts.body_md`, `email_templates.body_md`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->longText('question_md')->nullable(false)->change();
            $table->longText('handout_md')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->text('question_md')->nullable(false)->change();
            $table->text('handout_md')->nullable()->change();
        });
    }
};
