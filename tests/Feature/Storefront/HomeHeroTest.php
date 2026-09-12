<?php

namespace Tests\Feature\Storefront;

use App\Models\Course;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 002 US20 + US21 — the homepage hero.
 *
 * The invariant that needs a test is FR-070: `hero_promo_course_id` is a bare
 * reference with no foreign key behind it. Deleting the course or pulling it
 * back to draft never comes back to clear that key — so every read has to
 * re-check, and the homepage has to survive it.
 *
 * The subscribe form itself is covered by HeroSubscribeTest; what belongs here
 * is that the 📌 line and the form are no longer tied together (US21).
 */
class HomeHeroTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create(['email' => 'admin@example.com', 'role' => 'admin']);
    }

    private function course(array $overrides = []): Course
    {
        return Course::create(array_merge([
            'name'            => '聰明工作者的 10 堂體驗課',
            'slug'            => 'smart-worker-10',
            'tagline'         => 't',
            'description'     => 'd',
            'price'           => 0,
            'instructor_name' => 'I',
            'type'            => 'mini',
            'status'          => 'selling',
            'is_published'    => true,
            'is_visible'      => true,
            'course_type'     => 'drip',
        ], $overrides));
    }

    private function heroProps(): array
    {
        $props = [];

        $this->get('/')->assertOk()->assertInertia(function ($page) use (&$props) {
            $props = $page->toArray()['props'];
        });

        return $props;
    }

    // --- 📌 推薦那行的顯示條件（FR-070） ---------------------------------------

    public function test_no_promo_line_when_no_course_is_configured(): void
    {
        $this->course();

        $this->assertNull($this->heroProps()['heroPromo']);
    }

    public function test_promo_block_carries_the_course_name_and_sales_page_url(): void
    {
        $course = $this->course();
        SiteSetting::set('hero_promo_course_id', (string) $course->id);

        $promo = $this->heroProps()['heroPromo'];

        $this->assertSame($course->id, $promo['course_id']);
        $this->assertSame('聰明工作者的 10 堂體驗課', $promo['name']);
        $this->assertSame('/course/smart-worker-10', $promo['url']);
    }

    public function test_promo_block_falls_back_to_the_id_when_the_course_has_no_slug(): void
    {
        $course = $this->course(['slug' => null]);
        SiteSetting::set('hero_promo_course_id', (string) $course->id);

        $this->assertSame("/course/{$course->id}", $this->heroProps()['heroPromo']['url']);
    }

    public function test_promo_block_disappears_when_the_configured_course_is_deleted(): void
    {
        $course = $this->course();
        SiteSetting::set('hero_promo_course_id', (string) $course->id);
        $course->delete();

        $this->assertNull($this->heroProps()['heroPromo']);
    }

    public function test_any_live_product_can_be_the_promo_target(): void
    {
        // US21 untied the 📌 line from the form, so "drip only" lost its reason
        // — the line is just a link to a sales page (FR-070).
        $course = $this->course(['course_type' => 'standard']);
        SiteSetting::set('hero_promo_course_id', (string) $course->id);

        $this->assertSame($course->id, $this->heroProps()['heroPromo']['course_id']);
    }

    public function test_promo_block_disappears_when_the_course_is_unpublished(): void
    {
        $course = $this->course();
        SiteSetting::set('hero_promo_course_id', (string) $course->id);
        $course->update(['is_published' => false]);

        $this->assertNull($this->heroProps()['heroPromo']);
    }

    public function test_a_course_kept_off_the_homepage_list_can_still_be_the_claim_target(): void
    {
        // `is_visible = false` means "do not list this on the homepage", not
        // "this product is gone" — and a drip lead magnet is normally off that
        // list, because it is reached from an ad, not from browsing. The sales
        // page does not gate on it either.
        $course = $this->course(['is_visible' => false]);
        SiteSetting::set('hero_promo_course_id', (string) $course->id);

        $this->assertSame($course->id, $this->heroProps()['heroPromo']['course_id']);
    }

    public function test_promo_block_disappears_when_the_course_is_still_a_draft(): void
    {
        $course = $this->course();
        SiteSetting::set('hero_promo_course_id', (string) $course->id);
        $course->update(['status' => 'draft']);

        $this->assertNull($this->heroProps()['heroPromo']);
    }

    // --- 三塊文字（FR-053） ----------------------------------------------

    public function test_each_text_block_is_independent(): void
    {
        SiteSetting::set('hero_title', "幫助忙碌的現代人\n聰明工作，好好生活");

        $hero = $this->heroProps()['hero'];

        $this->assertSame("幫助忙碌的現代人\n聰明工作，好好生活", $hero['title']);
        $this->assertNull($hero['subtitle']);
        $this->assertNull($hero['description']);
    }

    public function test_the_subtitle_is_a_setting_of_its_own(): void
    {
        SiteSetting::set('hero_subtitle', '探討現代人的數位工作方案');

        $this->assertSame('探討現代人的數位工作方案', $this->heroProps()['hero']['subtitle']);
    }

    // --- 廢除的設定（FR-055 / FR-061 / FR-062） ---------------------------

    public function test_the_retired_cta_button_is_gone_from_both_payloads(): void
    {
        $hero = $this->heroProps()['hero'];

        $this->assertArrayNotHasKey('button_label', $hero);
        $this->assertArrayNotHasKey('button_url', $hero);

        $this->actingAs($this->admin())->get('/admin/homepage')->assertInertia(function ($page) {
            $settings = $page->toArray()['props']['settings'];

            $this->assertArrayNotHasKey('hero_button_label', $settings);
            $this->assertArrayNotHasKey('hero_button_url', $settings);
        });
    }

    public function test_the_owner_avatar_is_gone_but_the_intro_text_stays(): void
    {
        SiteSetting::set('sns_section_enabled', '1');
        SiteSetting::set('sns_profile_intro', '我是站長');

        $profile = $this->heroProps()['snsProfile'];

        $this->assertSame('我是站長', $profile['intro']);
        $this->assertArrayNotHasKey('image_url', $profile);

        $this->actingAs($this->admin())->get('/admin/homepage')->assertInertia(function ($page) {
            $settings = $page->toArray()['props']['settings'];

            $this->assertArrayNotHasKey('sns_profile_image_url', $settings);
            $this->assertSame('我是站長', $settings['sns_profile_intro']);
        });
    }

    public function test_the_avatar_delete_route_is_gone(): void
    {
        $this->actingAs($this->admin())
            ->delete('/admin/homepage/sns-profile-image')
            ->assertNotFound();
    }

    // --- 後台（FR-056） ---------------------------------------------------

    public function test_the_admin_page_offers_every_live_course_as_a_promo_target(): void
    {
        $drip = $this->course();
        $standard = $this->course(['name' => '一般課', 'slug' => 'standard-one', 'course_type' => 'standard']);
        $this->course(['name' => '草稿課', 'slug' => 'draft-one', 'status' => 'draft']);
        SiteSetting::set('hero_promo_course_id', (string) $standard->id);

        $this->actingAs($this->admin())->get('/admin/homepage')->assertInertia(function ($page) use ($drip, $standard) {
            $props = $page->toArray()['props'];
            $ids = array_column($props['promoCourses'], 'id');

            $this->assertEqualsCanonicalizing([$drip->id, $standard->id], $ids, '草稿課不該出現在候選裡');
            $this->assertSame((string) $standard->id, (string) $props['settings']['hero_promo_course_id']);
        });
    }

    public function test_an_admin_can_save_the_subtitle_and_the_promo_course(): void
    {
        $course = $this->course();

        $this->actingAs($this->admin())->post('/admin/homepage', [
            'hero_title'            => '大標',
            'hero_subtitle'         => '副標',
            'hero_description'      => '網站介紹',
            'hero_promo_course_id'  => $course->id,
            'sns_section_enabled'   => true,
        ])->assertRedirect();

        $this->assertSame('副標', SiteSetting::get('hero_subtitle'));
        $this->assertSame((string) $course->id, (string) SiteSetting::get('hero_promo_course_id'));
    }

    public function test_clearing_the_promo_course_hides_the_line(): void
    {
        $course = $this->course();
        SiteSetting::set('hero_promo_course_id', (string) $course->id);

        $this->actingAs($this->admin())->post('/admin/homepage', [
            'hero_promo_course_id' => '',
            'sns_section_enabled'  => true,
        ])->assertRedirect();

        $this->assertNull($this->heroProps()['heroPromo']);
    }

    public function test_the_promo_course_must_exist(): void
    {
        $this->actingAs($this->admin())->post('/admin/homepage', [
            'hero_promo_course_id' => 999999,
            'sns_section_enabled'  => true,
        ])->assertSessionHasErrors('hero_promo_course_id');
    }

    public function test_the_subscribe_form_does_not_depend_on_the_promo_setting(): void
    {
        // US21: with no promo product set the 📌 line is gone, but the hero
        // still renders — the form it carries is the newsletter's (FR-063).
        $this->get('/')->assertOk()->assertInertia(fn ($page) => $page->where('heroPromo', null));
    }

    public function test_a_tall_portrait_hero_image_is_accepted(): void
    {
        // The old `min_width=1200` rule was written for a full-bleed banner;
        // the new right-hand portrait is commonly narrower than that (FR-054).
        \Illuminate\Support\Facades\Storage::fake('public');

        $this->actingAs($this->admin())->post('/admin/homepage', [
            'sns_section_enabled' => true,
            'hero_banner' => \Illuminate\Http\UploadedFile::fake()->image('portrait.jpg', 800, 1000),
        ])->assertSessionHasNoErrors();

        $this->assertNotNull(SiteSetting::get('hero_banner_path'));
    }
}
