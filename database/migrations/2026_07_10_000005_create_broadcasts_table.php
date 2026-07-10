<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('broadcasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->string('subject');
            // Plain string (not enum) so new statuses like 'scheduled' don't require an
            // enum ALTER — and sqlite (test DB) doesn't bake a value-restricting CHECK.
            $table->string('status')->default('draft');
            $table->unsignedInteger('recipients_count')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('post_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('broadcasts');
    }
};
