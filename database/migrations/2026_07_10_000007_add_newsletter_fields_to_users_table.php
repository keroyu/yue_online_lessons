<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('newsletter_status', ['none', 'subscribed', 'unsubscribed', 'dormant'])
                ->default('none')
                ->after('role');
            $table->timestamp('newsletter_subscribed_at')->nullable()->after('newsletter_status');
            $table->uuid('newsletter_unsubscribe_token')->nullable()->unique()->after('newsletter_subscribed_at');
            $table->timestamp('newsletter_last_opened_at')->nullable()->after('newsletter_unsubscribe_token');
            $table->timestamp('newsletter_status_changed_at')->nullable()->after('newsletter_last_opened_at');

            $table->index('newsletter_status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['newsletter_status']);
            $table->dropColumn([
                'newsletter_status',
                'newsletter_subscribed_at',
                'newsletter_unsubscribe_token',
                'newsletter_last_opened_at',
                'newsletter_status_changed_at',
            ]);
        });
    }
};
