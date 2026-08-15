<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Traffic attribution for free claims (002 US17 / FR-037).
 *
 * Column names and types mirror `orders` exactly (2026_05_08_000001 and
 * 2026_07_12_000004): the course traffic report applies one field list and one
 * resolveSource() ruleset across orders, purchases and drip_subscriptions, so
 * a name drifting here would silently drop a source from the report.
 *
 * Only meaningful on rows with source = 'free'. A checkout purchase carries its
 * attribution on the matching order, and the two are never compared.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->string('utm_source', 100)->nullable()->after('webhook_received_at');
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
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropIndex(['utm_source']);
            $table->dropIndex(['referrer_domain']);
            $table->dropColumn([
                'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content',
                'referrer_domain', 'gclid', 'fbclid', 'ttclid', 'first_touch',
            ]);
        });
    }
};
