<?php

namespace Tests\Feature\Storefront;

use App\Models\HomepageWidget;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * US9 — 站長介紹（site_settings KV，無 migration）。
 *
 * 形象圖已隨 US20 退役（FR-061）：站長的照片從此只有首頁 hero 一個位置，
 * 原本釘住上傳／替換／刪除的三條測試連同功能一起移除，介紹文字不受影響。
 *
 * 寫入路徑已隨 US24 搬到 `POST /admin/homepage/sns-profile`（FR-087），
 * 那兩條測試改由 `HomepageWidgetSettingsTest` 承接。這裡只剩前台呈現：
 * 介紹文字是否隨「追蹤站長」widget 的顯示狀態進入／離開 page payload。
 */
class SnsProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_exposes_sns_profile_when_enabled(): void
    {
        HomepageWidget::where('key', 'social')->update(['is_visible' => true]);
        SiteSetting::set('sns_profile_intro', '站長的一段介紹');

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($p) => $p->where('snsProfile.intro', '站長的一段介紹'));
    }

    public function test_home_hides_sns_profile_when_section_disabled(): void
    {
        HomepageWidget::where('key', 'social')->update(['is_visible' => false]);
        SiteSetting::set('sns_profile_intro', '站長的一段介紹');

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn ($p) => $p->where('snsProfile', null));
    }
}
