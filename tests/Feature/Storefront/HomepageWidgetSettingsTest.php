<?php

namespace Tests\Feature\Storefront;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 002 US24 — 站長介紹 leaves the hero form (FR-087).
 *
 * The modals themselves are Vue and get verified on screen; what is worth
 * pinning in PHP is the endpoint split, because the failure mode is silent:
 * if the hero form still wrote `sns_profile_intro`, a save from the hero card
 * would quietly overwrite whatever the SNS modal had just stored with an empty
 * string, and nothing on screen would say so.
 */
class HomepageWidgetSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::firstOrCreate(['email' => 'admin@example.com'], ['role' => 'admin']);
    }

    private function member(): User
    {
        return User::firstOrCreate(['email' => 'member@example.com'], ['role' => 'user']);
    }

    public function test_the_new_endpoint_saves_the_intro(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/homepage/sns-profile', ['intro' => '嗨，我是站長。'])
            ->assertRedirect();

        $this->assertSame('嗨，我是站長。', SiteSetting::get('sns_profile_intro'));
    }

    public function test_the_intro_is_capped_at_500_chars(): void
    {
        $this->actingAs($this->admin())
            ->from('/admin/homepage')
            ->post('/admin/homepage/sns-profile', ['intro' => str_repeat('字', 501)])
            ->assertSessionHasErrors('intro');
    }

    public function test_a_blank_intro_clears_it(): void
    {
        SiteSetting::set('sns_profile_intro', '舊的介紹');

        $this->actingAs($this->admin())
            ->post('/admin/homepage/sns-profile', ['intro' => ''])
            ->assertRedirect();

        $this->assertSame('', SiteSetting::get('sns_profile_intro'));
    }

    /**
     * The hero form must no longer touch it — otherwise saving the hero card
     * wipes an intro the admin set in the SNS modal five seconds earlier.
     */
    public function test_the_hero_form_no_longer_writes_the_intro(): void
    {
        SiteSetting::set('sns_profile_intro', '由 SNS modal 存進去的');

        $this->actingAs($this->admin())
            ->post('/admin/homepage', [
                'hero_title'        => '大標',
                'sns_profile_intro' => '偷渡進來的值',
            ])
            ->assertRedirect();

        $this->assertSame('由 SNS modal 存進去的', SiteSetting::get('sns_profile_intro'));
        $this->assertSame('大標', SiteSetting::get('hero_title'));
    }

    public function test_the_admin_page_still_ships_the_intro(): void
    {
        SiteSetting::set('sns_profile_intro', '目前的介紹');

        $this->actingAs($this->admin())
            ->get('/admin/homepage')
            ->assertInertia(fn ($page) => $page->where('settings.sns_profile_intro', '目前的介紹'));
    }

    public function test_non_admin_cannot_change_the_intro(): void
    {
        SiteSetting::set('sns_profile_intro', '原本的');

        $this->actingAs($this->member())
            ->post('/admin/homepage/sns-profile', ['intro' => '被改掉的'])
            ->assertRedirect('/');

        $this->assertSame('原本的', SiteSetting::get('sns_profile_intro'));
    }
}
