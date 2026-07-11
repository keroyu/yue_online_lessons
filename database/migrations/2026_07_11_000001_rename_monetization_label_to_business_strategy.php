<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Data migration: rename the "知識變現" label to "商業策略" everywhere it is stored
 * as data (not code). Runs on every environment via `php artisan migrate`, so the
 * remote host is updated on deploy without a manual SSH edit. Idempotent.
 *
 *   1. Post tag  : tags.name 「知識變現」→「商業策略」(slug 'monetization' unchanged)
 *   2. Course content-category label saved in site_settings.content_categories
 *      (slug 'monetization' → label '商業策略')
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Post tag rename (keep slug so existing /blog/tag URLs stay valid).
        DB::table('tags')->where('name', '知識變現')->update(['name' => '商業策略']);

        // 2. Course content-category label (stored as JSON in site_settings).
        $this->relabelContentCategory('商業策略');
    }

    public function down(): void
    {
        DB::table('tags')->where('name', '商業策略')->update(['name' => '知識變現']);
        $this->relabelContentCategory('知識變現');
    }

    private function relabelContentCategory(string $label): void
    {
        $row = DB::table('site_settings')->where('key', 'content_categories')->first();
        if (! $row) {
            return;
        }

        $cats = json_decode($row->value, true);
        if (! is_array($cats)) {
            return;
        }

        foreach ($cats as &$cat) {
            if (($cat['slug'] ?? '') === 'monetization') {
                $cat['label'] = $label;
            }
        }
        unset($cat);

        DB::table('site_settings')
            ->where('key', 'content_categories')
            ->update(['value' => json_encode(array_values($cats), JSON_UNESCAPED_UNICODE)]);
    }
};
