<?php

namespace Tests\Feature\Storefront;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 站台資訊（站名 / 經營者 / 地址）— site_settings KV, edited under 首頁設定.
 *
 * These three were literals in the Vue source until this feature: the point of
 * the tests is that nothing reads a literal any more, so a second install of
 * this codebase renders its own name rather than ours.
 */
class SiteIdentityTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create(['email' => 'admin@example.com', 'role' => 'admin']);
    }

    public function test_admin_saves_the_three_values(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/homepage/site-identity', [
                'site_name'     => '測試站台',
                'site_operator' => '測試有限公司',
                'site_address'  => '臺北市中正區測試路 1 號',
            ])
            ->assertRedirect();

        $this->assertSame('測試站台', SiteSetting::get('site_name'));
        $this->assertSame('測試有限公司', SiteSetting::get('site_operator'));
        $this->assertSame('臺北市中正區測試路 1 號', SiteSetting::get('site_address'));
    }

    public function test_site_name_is_required(): void
    {
        $this->actingAs($this->admin())
            ->from('/admin/homepage')
            ->post('/admin/homepage/site-identity', ['site_name' => ''])
            ->assertSessionHasErrors('site_name');
    }

    public function test_operator_and_address_may_be_blank(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/homepage/site-identity', ['site_name' => '只有站名'])
            ->assertRedirect();

        $this->assertSame('', SiteSetting::get('site_operator'));
        $this->assertSame('', SiteSetting::get('site_address'));
    }

    public function test_guests_cannot_change_it(): void
    {
        $this->post('/admin/homepage/site-identity', ['site_name' => '駭客站'])
            ->assertRedirect('/login');

        $this->assertNull(SiteSetting::get('site_name'));
    }

    public function test_identity_is_shared_with_every_page(): void
    {
        SiteSetting::set('site_name', '測試站台');
        SiteSetting::set('site_operator', '測試有限公司');
        SiteSetting::set('site_address', '臺北市中正區測試路 1 號');

        $this->get('/')->assertInertia(fn ($page) => $page
            ->where('site.name', '測試站台')
            ->where('site.operator', '測試有限公司')
            ->where('site.address', '臺北市中正區測試路 1 號'));
    }

    /**
     * Guards the sweep. The brand name used to be a literal in fifteen files;
     * a setting that only half the app respects is worse than no setting,
     * because the half that ignores it is the half nobody thinks to check.
     */
    public function test_no_source_file_hardcodes_the_brand(): void
    {
        $legacy = ['經營者時間銀行', '投好壯壯有限公司', '臺北市文山區辛亥路4段128之1號1樓'];
        $offenders = [];

        foreach ([base_path('app'), base_path('resources')] as $root) {
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root)) as $file) {
                if (! $file->isFile() || ! in_array($file->getExtension(), ['php', 'vue', 'blade'], true)) {
                    continue;
                }

                $path = $file->getPathname();
                $body = (string) file_get_contents($path);

                foreach ($legacy as $needle) {
                    // A placeholder in the admin form would be the one excusable
                    // copy — there is none, and there should not be one.
                    if (str_contains($body, $needle)) {
                        $offenders[] = str_replace(base_path() . '/', '', $path);
                        break;
                    }
                }
            }
        }

        $this->assertSame([], $offenders, '站名／經營者／地址應改讀 site_settings（SiteSetting::siteName() 或共享的 site prop）');
    }

    public function test_site_name_falls_back_to_the_homepage_hero_title(): void
    {
        // Installs that predate the setting keep the name their hero already
        // showed, rather than dropping back to whatever APP_NAME happens to be.
        SiteSetting::set('hero_title', '舊站名');

        $this->assertSame('舊站名', SiteSetting::siteName());
    }
}
