<?php

namespace Tests\Feature\Storefront;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * US9 — 站長介紹（site_settings KV，無 migration）。
 *
 * 形象圖已隨 US20 退役（FR-061）：站長的照片從此只有首頁 hero 一個位置，
 * 原本釘住上傳／替換／刪除的三條測試連同功能一起移除，介紹文字不受影響。
 */
class SnsProfileTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create(['email' => 'admin@example.com', 'role' => 'admin']);
    }

    public function test_update_saves_intro(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/homepage', [
                'sns_section_enabled' => 1,
                'sns_profile_intro'   => '嗨，我是站長。',
            ])
            ->assertRedirect();

        $this->assertSame('嗨，我是站長。', SiteSetting::get('sns_profile_intro'));
    }

    public function test_intro_over_500_chars_is_rejected(): void
    {
        $this->actingAs($this->admin())
            ->from('/admin/homepage')
            ->post('/admin/homepage', [
                'sns_section_enabled' => 1,
                'sns_profile_intro'   => str_repeat('字', 501),
            ])
            ->assertSessionHasErrors('sns_profile_intro');
    }

    public function test_home_exposes_sns_profile_when_enabled(): void
    {
        SiteSetting::set('sns_section_enabled', '1');
        SiteSetting::set('sns_profile_intro', '站長的一段介紹');

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($p) => $p->where('snsProfile.intro', '站長的一段介紹'));
    }

    public function test_home_hides_sns_profile_when_section_disabled(): void
    {
        SiteSetting::set('sns_section_enabled', '0');
        SiteSetting::set('sns_profile_intro', '站長的一段介紹');

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($p) => $p->where('snsProfile', null));
    }
}
