<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The other half of 002 US17 / FR-037.
 *
 * A drip claim from the sales page goes through POST /drip/subscribe and never
 * touches `purchases`, so attribution has to live here too. Deliberately a
 * second copy of the same columns rather than forcing every claim to mint a
 * zero-value Purchase: `purchases` is the site-wide truth for "owns this
 * course" and is read by member details, new-lesson notifications, gift
 * de-duplication and hasPaidAccessForUser() (D45).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('drip_subscriptions', function (Blueprint $table) {
            $table->string('utm_source', 100)->nullable()->after('unsubscribe_token');
            $table->string('utm_medium', 100)->nullable()->after('utm_source');
            $table->string('utm_campaign', 100)->nullable()->after('utm_medium');
            $table->string('utm_term', 100)->nullable()->after('utm_campaign');
            $table->string('utm_content', 100)->nullable()->after('utm_term');
            $table->string('referrer_domain', 255)->nullable()->after('utm_content');
            $table->string('gclid', 255)->nullable()->after('referrer_domain');
            $table->string('fbclid', 255)->nullable()->after('gclid');
            $table->string('ttclid', 255)->nullable()->after('fbclid');
            $table->json('first_touch')->nullable()->after('ttclid');

            $table->index('utm_source');
            $table->index('referrer_domain');
        });
    }

    public function down(): void
    {
        Schema::table('drip_subscriptions', function (Blueprint $table) {
            $table->dropIndex(['utm_source']);
            $table->dropIndex(['referrer_domain']);
            $table->dropColumn([
                'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content',
                'referrer_domain', 'gclid', 'fbclid', 'ttclid', 'first_touch',
            ]);
        });
    }
};
