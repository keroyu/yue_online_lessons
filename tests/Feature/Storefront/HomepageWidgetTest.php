<?php

namespace Tests\Feature\Storefront;

use App\Models\Course;
use App\Models\HomepageFeaturedCourse;
use App\Models\HomepageWidget;
use App\Models\Post;
use App\Models\User;
use App\Services\HomepageWidgetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 002 US23 — the homepage's blocks become data.
 *
 * What is worth pinning here is not "drag saves an order" but the four rules
 * that are invisible on screen and expensive to get wrong: built-ins cannot be
 * edited or deleted through the API (FR-074), a hidden block's data never
 * reaches the payload (FR-076), a missing built-in row repairs itself
 * (FR-073), and a reorder posted from a stale tab cannot lose a widget
 * (FR-081).
 */
class HomepageWidgetTest extends TestCase
{
    use RefreshDatabase;

    /** Memoised: several tests act as the same person more than once. */
    private function admin(): User
    {
        return User::firstOrCreate(['email' => 'admin@example.com'], ['role' => 'admin']);
    }

    private function member(): User
    {
        return User::firstOrCreate(['email' => 'member@example.com'], ['role' => 'user']);
    }

    private function course(string $name): Course
    {
        return Course::create([
            'name'            => $name,
            'slug'            => 'c-'.uniqid(),
            'tagline'         => 't',
            'description'     => 'd',
            'price'           => 1000,
            'instructor_name' => 'I',
            'type'            => 'mini',
            'status'          => 'selling',
            'course_type'     => 'standard',
        ]);
    }

    private function publishedPost(string $title): Post
    {
        return Post::create([
            'title'        => $title,
            'slug'         => 'p-'.uniqid(),
            'body_md'      => '內文',
            'excerpt'      => '摘要',
            'status'       => 'published',
            'published_at' => now()->subDay(),
        ]);
    }

    private function custom(array $overrides = []): HomepageWidget
    {
        return HomepageWidget::create(array_merge([
            'key'        => null,
            'type'       => HomepageWidget::TYPE_HTML,
            'area'       => HomepageWidget::AREA_SIDE,
            'title'      => '公告',
            'html'       => '<p>優惠中</p>',
            'sort_order' => 99,
            'is_visible' => true,
        ], $overrides));
    }

    // ── FR-073: the const is the source of truth, rows are its projection ────

    public function test_missing_builtin_rows_are_recreated_on_read(): void
    {
        HomepageWidget::query()->delete();

        $keys = app(HomepageWidgetService::class)->all()->pluck('key')->all();

        foreach (array_keys(HomepageWidgetService::BUILTIN) as $expected) {
            $this->assertContains($expected, $keys);
        }
    }

    public function test_a_builtin_row_with_an_unknown_key_is_not_rendered(): void
    {
        HomepageWidget::create([
            'key'        => 'retired_block',
            'type'       => HomepageWidget::TYPE_BUILTIN,
            'area'       => HomepageWidget::AREA_SIDE,
            'title'      => '已退役',
            'sort_order' => 0,
            'is_visible' => true,
        ]);

        $keys = collect(app(HomepageWidgetService::class)->descriptors(HomepageWidget::AREA_SIDE))
            ->pluck('key')->all();

        $this->assertNotContains('retired_block', $keys);
    }

    // ── FR-074: built-ins are sort/visibility only ───────────────────────────

    public function test_builtin_cannot_be_edited(): void
    {
        $builtin = HomepageWidget::where('key', 'course_catalog')->firstOrFail();

        $this->actingAs($this->admin())
            ->put("/admin/homepage/widgets/{$builtin->id}", [
                'title' => '改名字',
                'html'  => '<p>x</p>',
                'area'  => HomepageWidget::AREA_SIDE,
            ])
            ->assertForbidden();

        $this->assertSame(HomepageWidget::AREA_MAIN, $builtin->fresh()->area);
    }

    public function test_builtin_cannot_be_deleted(): void
    {
        $builtin = HomepageWidget::where('key', 'course_catalog')->firstOrFail();

        $this->actingAs($this->admin())
            ->delete("/admin/homepage/widgets/{$builtin->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('homepage_widgets', ['key' => 'course_catalog']);
    }

    public function test_builtin_visibility_can_be_toggled(): void
    {
        $builtin = HomepageWidget::where('key', 'blog')->firstOrFail();

        $this->actingAs($this->admin())
            ->patch("/admin/homepage/widgets/{$builtin->id}/visibility", ['is_visible' => false])
            ->assertRedirect();

        $this->assertFalse($builtin->fresh()->is_visible);
    }

    /** The server sets the value it was given; it never flips (cf. FR-051). */
    public function test_visibility_is_set_not_toggled(): void
    {
        $builtin = HomepageWidget::where('key', 'blog')->firstOrFail();
        $admin = $this->admin();

        $this->actingAs($admin)->patch("/admin/homepage/widgets/{$builtin->id}/visibility", ['is_visible' => false]);
        $this->actingAs($admin)->patch("/admin/homepage/widgets/{$builtin->id}/visibility", ['is_visible' => false]);

        $this->assertFalse($builtin->fresh()->is_visible);
    }

    // ── FR-076: hidden means absent from the payload, not hidden in Vue ──────

    public function test_hidden_featured_courses_widget_keeps_its_data_out_of_the_payload(): void
    {
        $this->feature('不該外流的課程名');

        HomepageWidget::where('key', 'featured_courses')->update(['is_visible' => false]);

        $this->get('/')
            ->assertInertia(fn ($page) => $page
                ->where('featuredCourses', [])
                ->where('sideWidgets', fn ($widgets) => collect($widgets)->pluck('key')->doesntContain('featured_courses'))
            );
    }

    public function test_hidden_course_catalog_keeps_courses_out_of_the_payload(): void
    {
        $this->course('隱藏中的課程');

        HomepageWidget::where('key', 'course_catalog')->update(['is_visible' => false]);

        $this->get('/')->assertInertia(fn ($page) => $page->where('courses', []));
    }

    public function test_hidden_popular_posts_keeps_posts_out_of_the_payload(): void
    {
        $this->publishedPost('熱門文章標題');

        HomepageWidget::where('key', 'popular_posts')->update(['is_visible' => false]);

        $this->get('/')->assertInertia(fn ($page) => $page->where('popularPosts', []));
    }

    // ── FR-082: the side column is one thing across homepage and blog ────────

    public function test_custom_side_widget_appears_on_both_homepage_and_blog_article(): void
    {
        $this->custom(['title' => '側欄公告', 'html' => '<p>HELLO</p>']);
        $post = $this->publishedPost('一篇文章');

        foreach (['/', "/blog/{$post->slug}"] as $url) {
            $this->get($url)->assertInertia(
                fn ($page) => $page->where(
                    'sideWidgets',
                    fn ($widgets) => collect($widgets)->contains(fn ($w) => ($w['html'] ?? null) === '<p>HELLO</p>')
                )
            );
        }
    }

    public function test_main_widgets_are_homepage_only(): void
    {
        $post = $this->publishedPost('一篇文章');

        $this->get("/blog/{$post->slug}")
            ->assertInertia(fn ($page) => $page->missing('mainWidgets'));
    }

    // ── FR-081: a reorder from a stale tab must not lose anything ────────────

    public function test_reorder_ignores_ids_from_the_other_column_and_appends_missing_ones(): void
    {
        $side = HomepageWidget::area(HomepageWidget::AREA_SIDE)->ordered()->pluck('id')->all();
        $mainId = HomepageWidget::where('key', 'course_catalog')->value('id');

        // A stale tab posts the side column reversed, without the last widget,
        // and with a main-column id mixed in.
        $posted = [$side[1], $side[0], $mainId];

        $this->actingAs($this->admin())
            ->post('/admin/homepage/widgets/reorder', [
                'area' => HomepageWidget::AREA_SIDE,
                'ids'  => $posted,
            ])
            ->assertRedirect();

        $after = HomepageWidget::area(HomepageWidget::AREA_SIDE)->ordered()->pluck('id')->all();

        $this->assertSame([$side[1], $side[0], $side[2]], $after);
        $this->assertSame(HomepageWidget::AREA_MAIN, HomepageWidget::find($mainId)->area);
    }

    // ── Custom widget CRUD ───────────────────────────────────────────────────

    public function test_admin_can_create_edit_and_delete_a_custom_widget(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/homepage/widgets', [
            'title' => '公告',
            'html'  => '<p style="color: var(--color-brand-gold)">嗨</p>',
            'area'  => HomepageWidget::AREA_MAIN,
        ])->assertRedirect();

        $widget = HomepageWidget::where('type', HomepageWidget::TYPE_HTML)->firstOrFail();
        $this->assertNull($widget->key);
        $this->assertSame(HomepageWidget::AREA_MAIN, $widget->area);

        $this->actingAs($admin)->put("/admin/homepage/widgets/{$widget->id}", [
            'title' => '新公告',
            'html'  => '<p>改過了</p>',
            'area'  => HomepageWidget::AREA_SIDE,
        ])->assertRedirect();

        $widget->refresh();
        $this->assertSame('新公告', $widget->title);
        $this->assertSame(HomepageWidget::AREA_SIDE, $widget->area);

        $this->actingAs($admin)->delete("/admin/homepage/widgets/{$widget->id}")->assertRedirect();
        $this->assertDatabaseMissing('homepage_widgets', ['id' => $widget->id]);
    }

    public function test_custom_widget_without_a_title_renders_bare(): void
    {
        $this->custom(['title' => null, 'html' => '<p>沒有標題</p>']);

        $this->get('/')->assertInertia(
            fn ($page) => $page->where(
                'sideWidgets',
                fn ($widgets) => collect($widgets)->contains(
                    fn ($w) => $w['type'] === 'html' && $w['title'] === null
                )
            )
        );
    }

    public function test_html_is_required_and_capped(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/homepage/widgets', ['title' => '無內容', 'area' => HomepageWidget::AREA_SIDE])
            ->assertSessionHasErrors('html');

        $this->actingAs($this->admin())
            ->post('/admin/homepage/widgets', [
                'html' => str_repeat('a', 20001),
                'area' => HomepageWidget::AREA_SIDE,
            ])
            ->assertSessionHasErrors('html');
    }

    // ── Upgrade path (FR-077) ────────────────────────────────────────────────

    public function test_builtin_descriptors_carry_no_admin_label(): void
    {
        $descriptors = app(HomepageWidgetService::class)->descriptors(HomepageWidget::AREA_SIDE);

        foreach ($descriptors as $d) {
            if ($d['type'] === HomepageWidget::TYPE_BUILTIN) {
                $this->assertNull($d['title']);
            }
        }
    }

    public function test_sns_widget_hidden_removes_social_links_from_payload(): void
    {
        HomepageWidget::where('key', 'social')->update(['is_visible' => false]);

        $this->get('/')->assertInertia(fn ($page) => $page
            ->where('socialLinks', [])
            ->where('snsProfile', null)
        );
    }

    // ── Authorisation ────────────────────────────────────────────────────────

    public function test_non_admin_cannot_touch_widgets(): void
    {
        $widget = $this->custom();

        $this->actingAs($this->member())
            ->post('/admin/homepage/widgets', [
                'html' => '<p>x</p>',
                'area' => HomepageWidget::AREA_SIDE,
            ])
            ->assertRedirect('/');

        $this->actingAs($this->member())
            ->delete("/admin/homepage/widgets/{$widget->id}")
            ->assertRedirect('/');

        $this->assertDatabaseHas('homepage_widgets', ['id' => $widget->id]);
        $this->assertSame(1, HomepageWidget::where('type', HomepageWidget::TYPE_HTML)->count());
    }

    private function feature(string $courseName): HomepageFeaturedCourse
    {
        return HomepageFeaturedCourse::create([
            'course_id'  => $this->course($courseName)->id,
            'blurb'      => '介紹',
            'sort_order' => 1,
        ]);
    }
}
