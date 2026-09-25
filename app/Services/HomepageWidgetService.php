<?php

namespace App\Services;

use App\Models\HomepageFeaturedCourse;
use App\Models\HomepageWidget;
use App\Models\Post;
use App\Models\SiteSetting;
use App\Models\SocialLink;
use Illuminate\Support\Collection;

/**
 * Which blocks the homepage is made of, in which order (002 US23).
 *
 * Replaces SidebarService: the right-hand column was only ever half the
 * problem, and both columns now read from the same table through the same
 * ordering and visibility rules.
 */
class HomepageWidgetService
{
    /**
     * The built-in widgets. This const is the single source of truth; the rows
     * in `homepage_widgets` are only its persisted projection (002 FR-073).
     *
     * Adding one here is enough — `sync()` creates the missing row on the next
     * read, on every install, without a migration that only helps databases
     * which have not run yet (002 D66).
     *
     * `title` is the label shown in the admin list, not a heading rendered on
     * the page: every built-in draws its own heading.
     */
    public const BUILTIN = [
        'popular_posts'    => ['area' => HomepageWidget::AREA_MAIN, 'title' => '熱門文章'],
        'course_catalog'   => ['area' => HomepageWidget::AREA_MAIN, 'title' => '所有資源（含內容分類按鈕）'],
        'featured_courses' => ['area' => HomepageWidget::AREA_SIDE, 'title' => '精選推薦（課程）'],
        'social'           => ['area' => HomepageWidget::AREA_SIDE, 'title' => '追蹤站長（SNS）'],
        'blog'             => ['area' => HomepageWidget::AREA_SIDE, 'title' => '近期文章（Blog）'],
    ];

    /** Memoised per request — several call sites ask the same question. */
    private ?Collection $widgets = null;

    /**
     * Every widget, ordered, with any missing built-in row created first.
     *
     * A built-in row whose key is no longer in BUILTIN is kept in the database
     * but dropped here: the widget it named does not exist any more, and the
     * front end has nothing to render for it.
     */
    public function all(): Collection
    {
        if ($this->widgets !== null) {
            return $this->widgets;
        }

        $widgets = HomepageWidget::ordered()->get();

        $missing = array_diff(
            array_keys(self::BUILTIN),
            $widgets->pluck('key')->filter()->all()
        );

        if ($missing !== []) {
            foreach ($missing as $key) {
                $area = self::BUILTIN[$key]['area'];

                HomepageWidget::create([
                    'key'        => $key,
                    'type'       => HomepageWidget::TYPE_BUILTIN,
                    'area'       => $area,
                    'title'      => self::BUILTIN[$key]['title'],
                    // Appended to the end of its own column: a new block must
                    // not push the admin's existing arrangement around.
                    'sort_order' => ((int) HomepageWidget::area($area)->max('sort_order')) + 1,
                    'is_visible' => true,
                ]);
            }

            $widgets = HomepageWidget::ordered()->get();
        }

        return $this->widgets = $widgets->reject(
            fn (HomepageWidget $w) => $w->isBuiltin() && ! isset(self::BUILTIN[$w->key])
        )->values();
    }

    /** All widgets of one column including hidden ones — the admin list. */
    public function column(string $area): Collection
    {
        return $this->all()->where('area', $area)->values();
    }

    /**
     * What the page renders, in order: visible widgets only.
     *
     * A built-in's descriptor carries no title — it draws its own heading, and
     * the stored title is an admin-list label that has no business in the page
     * payload.
     */
    public function descriptors(string $area): array
    {
        return $this->column($area)
            ->where('is_visible', true)
            ->map(fn (HomepageWidget $w) => [
                'id'    => $w->id,
                'key'   => $w->key,
                'type'  => $w->type,
                'title' => $w->isBuiltin() ? null : $w->title,
                'html'  => $w->isBuiltin() ? null : $w->html,
            ])->values()->all();
    }

    public function isVisible(string $key): bool
    {
        return (bool) $this->all()->firstWhere('key', $key)?->is_visible;
    }

    /**
     * The side column: descriptors plus the data its built-ins draw from.
     *
     * Shared by the homepage and `/blog/{slug}` — the right-hand column has
     * been one thing across both pages since US6 (002 FR-082).
     *
     * A hidden widget's data is never queried, let alone serialised: filtering
     * in Vue would leave the hidden course's name, thumbnail and blurb sitting
     * in the page source, and "not ready to show" is usually about exactly
     * that copy (002 FR-076, same reasoning as FR-050).
     */
    public function sideProps(): array
    {
        return [
            'sideWidgets'     => $this->descriptors(HomepageWidget::AREA_SIDE),
            'featuredCourses' => $this->isVisible('featured_courses') ? $this->featuredCourses() : [],
            'socialLinks'     => $this->isVisible('social') ? $this->socialLinks() : [],
            'snsProfile'      => $this->isVisible('social') ? $this->snsProfile() : null,
            'blogArticles'    => $this->isVisible('blog') ? $this->blogArticles() : [],
        ];
    }

    private function socialLinks(): array
    {
        return SocialLink::ordered()->get()->map(fn (SocialLink $link) => [
            'platform' => $link->platform,
            'url'      => $link->url,
        ])->values()->all();
    }

    /**
     * Owner intro shown above the SNS links.
     *
     * The avatar that used to live here retired with 002 US20 — the owner's
     * picture now has exactly one home, the homepage hero (FR-061).
     */
    private function snsProfile(): array
    {
        return ['intro' => SiteSetting::get('sns_profile_intro')];
    }

    /**
     * "近期文章" widget: pure chronology — "featured" belongs to the
     * homepage's "熱門文章" list instead (FR-031).
     */
    private function blogArticles(): array
    {
        return Post::published()
            ->orderByDesc('published_at')
            ->take(5)
            ->get(['slug', 'title', 'excerpt', 'cover_image_path', 'published_at'])
            ->map(fn (Post $post) => [
                'title'        => $post->title,
                'excerpt'      => $post->excerpt,
                'url'          => "/blog/{$post->slug}",
                'cover'        => $post->cover_url,
                'published_at' => $post->published_at?->timezone('Asia/Taipei')->toDateString(),
            ])->values()->all();
    }

    private function featuredCourses(): array
    {
        return HomepageFeaturedCourse::ordered()->visible()
            ->with('course:id,slug,name,thumbnail,course_type')
            ->get()
            ->filter(fn (HomepageFeaturedCourse $item) => $item->course !== null)
            ->map(fn (HomepageFeaturedCourse $item) => [
                'id'          => $item->course->id,
                'name'        => $item->course->name,
                'thumbnail'   => $item->course->thumbnail_url,
                'blurb'       => $item->blurb,
                'url'         => '/course/'.($item->course->slug ?: $item->course->id),
                'course_type' => $item->course->course_type,
            ])->values()->all();
    }
}
