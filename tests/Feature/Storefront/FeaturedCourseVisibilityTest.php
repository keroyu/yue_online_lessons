<?php

namespace Tests\Feature\Storefront;

use App\Models\Course;
use App\Models\HomepageFeaturedCourse;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 002 US19 — hiding one featured course without removing it.
 *
 * The invariant worth testing is where the filter lives (FR-050): hidden
 * courses must not reach the page payload at all, on either surface that
 * renders the sidebar, because "not promoting this one right now" is sometimes
 * precisely because its copy is not fit to be read yet. The admin list is the
 * mirror image — it must keep showing every row, or the switch becomes a
 * one-way door.
 */
class FeaturedCourseVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create(['email' => 'admin@example.com', 'role' => 'admin']);
    }

    private function course(string $name): Course
    {
        return Course::create([
            'name'            => $name,
            'slug'            => 'c-' . uniqid(),
            'tagline'         => 't',
            'description'     => 'd',
            'price'           => 1000,
            'instructor_name' => 'I',
            'type'            => 'mini',
            'status'          => 'selling',
            'course_type'     => 'standard',
        ]);
    }

    private function feature(Course $course, array $overrides = []): HomepageFeaturedCourse
    {
        return HomepageFeaturedCourse::create(array_merge([
            'course_id'  => $course->id,
            'blurb'      => '介紹文字',
            'sort_order' => 1,
        ], $overrides));
    }

    public function test_new_rows_are_visible_by_default(): void
    {
        $featured = $this->feature($this->course('預設顯示'));

        $this->assertTrue($featured->fresh()->is_visible);
    }

    public function test_a_hidden_course_is_absent_from_the_homepage_payload(): void
    {
        $this->feature($this->course('看得到的課'), ['sort_order' => 1]);
        $this->feature($this->course('被藏起來的課'), ['sort_order' => 2, 'is_visible' => false]);

        $this->get('/')->assertInertia(function ($page) {
            $names = collect($page->toArray()['props']['featuredCourses'])->pluck('name');

            $this->assertTrue($names->contains('看得到的課'));
            $this->assertFalse($names->contains('被藏起來的課'));
        });
    }

    public function test_the_blog_article_page_shares_the_same_filtered_list(): void
    {
        $this->feature($this->course('被藏起來的課'), ['is_visible' => false]);

        $post = Post::create([
            'title'        => '一篇文章',
            'slug'         => 'a-post',
            'body_md'      => '內文',
            'status'       => 'published',
            'published_at' => now()->subDay(),
        ]);

        $this->get("/blog/{$post->slug}")->assertInertia(function ($page) {
            $this->assertSame([], $page->toArray()['props']['featuredCourses']);
        });
    }

    public function test_hiding_every_row_leaves_an_empty_list_so_the_whole_widget_disappears(): void
    {
        $this->feature($this->course('一'), ['is_visible' => false]);
        $this->feature($this->course('二'), ['sort_order' => 2, 'is_visible' => false]);

        $this->get('/')->assertInertia(function ($page) {
            // FeaturedCourses.vue renders nothing for an empty array — that
            // `v-if` is what keeps an empty "精選推薦" box off the sidebar.
            $this->assertSame([], $page->toArray()['props']['featuredCourses']);
        });
    }

    public function test_the_admin_list_still_shows_hidden_rows(): void
    {
        $featured = $this->feature($this->course('被藏起來的課'), ['is_visible' => false]);

        $this->actingAs($this->admin())->get('/admin/homepage')->assertInertia(function ($page) use ($featured) {
            $rows = collect($page->toArray()['props']['featuredCourses']);
            $row = $rows->firstWhere('id', $featured->id);

            $this->assertNotNull($row, '隱藏的列必須留在後台清單裡');
            $this->assertFalse($row['is_visible']);
        });
    }

    public function test_toggling_works_in_both_directions(): void
    {
        $featured = $this->feature($this->course('一門課'));
        $admin = $this->admin();

        $this->actingAs($admin)
            ->patch("/admin/homepage/featured-courses/{$featured->id}/visibility", ['is_visible' => false])
            ->assertRedirect();

        $this->assertFalse($featured->fresh()->is_visible);

        $this->actingAs($admin)
            ->patch("/admin/homepage/featured-courses/{$featured->id}/visibility", ['is_visible' => true])
            ->assertRedirect();

        $this->assertTrue($featured->fresh()->is_visible);
    }

    public function test_toggling_leaves_the_blurb_and_the_order_alone(): void
    {
        $featured = $this->feature($this->course('一門課'), ['blurb' => '已經存好的介紹', 'sort_order' => 7]);

        $this->actingAs($this->admin())
            ->patch("/admin/homepage/featured-courses/{$featured->id}/visibility", ['is_visible' => false]);

        $featured->refresh();

        // The blurb textarea is a draft until 儲存介紹 is pressed (D53); a
        // visibility toggle must not decide its fate in either direction.
        $this->assertSame('已經存好的介紹', $featured->blurb);
        $this->assertSame(7, $featured->sort_order);
    }

    public function test_a_missing_or_invalid_value_is_rejected(): void
    {
        $featured = $this->feature($this->course('一門課'));

        $this->actingAs($this->admin())
            ->from('/admin/homepage')
            ->patch("/admin/homepage/featured-courses/{$featured->id}/visibility", [])
            ->assertSessionHasErrors('is_visible');

        $this->assertTrue($featured->fresh()->is_visible);
    }

    public function test_non_admins_cannot_toggle(): void
    {
        $featured = $this->feature($this->course('一門課'));
        $member = User::create(['email' => 'member@example.com', 'role' => 'member']);

        $this->actingAs($member)
            ->patch("/admin/homepage/featured-courses/{$featured->id}/visibility", ['is_visible' => false])
            ->assertRedirect('/');

        $this->assertTrue($featured->fresh()->is_visible);
    }
}
