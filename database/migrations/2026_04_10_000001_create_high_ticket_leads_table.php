<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('high_ticket_leads', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('email', 255);
            $table->unsignedBigInteger('course_id');
            $table->enum('status', ['pending', 'contacted', 'converted', 'closed'])->default('pending');
            $table->tinyInteger('notified_count')->unsigned()->default(0);
            $table->timestamp('last_notified_at')->nullable();
            $table->timestamp('booked_at');
            $table->timestamps();

            $table->index('email', 'idx_email');
            $table->index('status', 'idx_status');
            $table->index('course_id', 'idx_course_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('high_ticket_leads');
    }
};
