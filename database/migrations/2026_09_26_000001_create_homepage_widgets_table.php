<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Homepage widgets (002 US23).
 *
 * Both columns, both kinds of widget (built-in and admin-authored HTML) live in
 * this one table sharing one `sort_order` axis, because the whole point is that
 * a custom block can sit BETWEEN two built-in ones (002 D65). Two sources of
 * order cannot express that.
 *
 * Retires `sidebar_widget_order` (order now lives in rows) and
 * `sns_section_enabled` (visibility now lives on the `social` row, 002 FR-077).
 */
return new class extends Migration
{
    /** Side-column built-ins, in the order used when no saved order exists. */
    private const SIDE_DEFAULTS = ['featured_courses', 'social', 'blog'];

    /** Admin-list labels, kept out of the code path that renders the page. */
    private const LABELS = [
        'popular_posts'    => '熱門文章',
        'course_catalog'   => '所有資源（含內容分類按鈕）',
        'featured_courses' => '精選推薦（課程）',
        'social'           => '追蹤站長（SNS）',
        'blog'             => '近期文章（Blog）',
    ];

    public function up(): void
    {
        // `area`, not `column` — the latter is a MySQL reserved word and every
        // raw query touching it would need backticks forever.
        Schema::create('homepage_widgets', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->nullable()->unique();
            $table->string('type', 20);
            $table->string('area', 10);
            $table->string('title', 100)->nullable();
            $table->text('html')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();

            $table->index(['area', 'sort_order']);
        });

        $now = now();

        // 1. Side built-ins keep the order the admin already dragged them into.
        $savedOrder = json_decode((string) $this->setting('sidebar_widget_order', '[]'), true);
        $savedOrder = is_array($savedOrder)
            ? array_values(array_intersect($savedOrder, self::SIDE_DEFAULTS))
            : [];

        foreach (self::SIDE_DEFAULTS as $key) {
            if (! in_array($key, $savedOrder, true)) {
                $savedOrder[] = $key;
            }
        }

        // 2. The SNS section's old on/off switch becomes that row's visibility.
        //    Stored as "0"/"1" text — (bool)"0" is true in PHP, hence (int).
        //    A database with no such row at all is a fresh install, not someone
        //    who switched the section off, so it defaults to visible like every
        //    other widget does.
        $snsEnabled = (bool) (int) $this->setting('sns_section_enabled', '1');

        $rows = [];

        foreach (array_values($savedOrder) as $i => $key) {
            $rows[] = [
                'key'        => $key,
                'type'       => 'builtin',
                'area'       => 'side',
                'title'      => self::LABELS[$key],
                'html'       => null,
                'sort_order' => $i,
                'is_visible' => $key === 'social' ? $snsEnabled : true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // 3. Main built-ins in their current hard-coded order.
        foreach (['popular_posts', 'course_catalog'] as $i => $key) {
            $rows[] = [
                'key'        => $key,
                'type'       => 'builtin',
                'area'       => 'main',
                'title'      => self::LABELS[$key],
                'html'       => null,
                'sort_order' => $i,
                'is_visible' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('homepage_widgets')->insert($rows);

        // 4. Only now are the old keys safe to drop.
        DB::table('site_settings')
            ->whereIn('key', ['sidebar_widget_order', 'sns_section_enabled'])
            ->delete();
    }

    /**
     * Restores the two retired keys at their DEFAULTS only.
     *
     * Custom widgets cannot come back — they are content the admin wrote, and a
     * migration cannot invent it. Saying so plainly beats a `down()` that looks
     * like it reverses this one.
     */
    public function down(): void
    {
        $order = DB::table('homepage_widgets')
            ->where('area', 'side')
            ->where('type', 'builtin')
            ->orderBy('sort_order')
            ->pluck('key')
            ->all();

        $snsVisible = DB::table('homepage_widgets')->where('key', 'social')->value('is_visible');

        Schema::dropIfExists('homepage_widgets');

        $now = now();

        DB::table('site_settings')->upsert([
            [
                'key'        => 'sidebar_widget_order',
                'value'      => json_encode($order ?: self::SIDE_DEFAULTS),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key'        => 'sns_section_enabled',
                'value'      => $snsVisible ? '1' : '0',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ], ['key'], ['value', 'updated_at']);
    }

    private function setting(string $key, string $default): string
    {
        $value = DB::table('site_settings')->where('key', $key)->value('value');

        return $value === null ? $default : (string) $value;
    }
};
