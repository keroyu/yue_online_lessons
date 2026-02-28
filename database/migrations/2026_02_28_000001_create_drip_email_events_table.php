<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drip_email_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')
                ->constrained('drip_subscriptions')
                ->cascadeOnDelete();
            $table->foreignId('lesson_id')
                ->constrained('lessons')
                ->cascadeOnDelete();
            $table->enum('event_type', ['opened', 'clicked']);
            $table->string('target_url', 500)->nullable();
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['subscription_id', 'lesson_id', 'event_type']);
            $table->index('subscription_id');
            $table->index('lesson_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drip_email_events');
    }
};
