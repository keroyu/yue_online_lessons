<?php

use App\Models\SiteSetting;
use Illuminate\Database\Migrations\Migration;

/**
 * Retire `blog_rss_url` (002 US5).
 *
 * The sidebar's 近期文章 widget stopped reading an external feed when the site
 * grew its own posts (012) — `SidebarService` has queried `Post::published()`
 * since then. That left the setting, its admin field and `BlogRssService`
 * with no consumer, and the field's own hint ("留空則隱藏「近期文章」區塊")
 * had quietly become untrue.
 *
 * Dropping the row rather than leaving it: a setting nothing reads is worse
 * than no setting, because the next person to open the table has to prove
 * that for themselves.
 */
return new class extends Migration
{
    public function up(): void
    {
        SiteSetting::where('key', 'blog_rss_url')->delete();
    }

    /**
     * Nothing to restore. The value was a URL the operator typed, not data the
     * app derived, and no code path reads the key any more — recreating an
     * empty row would only put the confusion back.
     */
    public function down(): void
    {
        //
    }
};
