<?php

namespace Tests\Feature\Platform;

use App\Models\SiteSetting;
use App\Services\SiteIconService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Owner-specific defaults moved out of the code: an existing install keeps
 * what it had, a fresh install starts blank.
 */
class LegacyOwnerDefaultsTest extends TestCase
{
    use RefreshDatabase;

    private function runMigration(): void
    {
        $migration = require database_path('migrations/2026_10_20_000001_install_legacy_owner_defaults.php');
        $migration->up();
    }

    public function test_a_fresh_install_gets_none_of_the_original_owners_settings(): void
    {
        Storage::fake('public');

        $this->runMigration();

        $this->assertSame('', (string) SiteSetting::get('support_email', ''));
        $this->assertSame('', (string) SiteSetting::get('high_ticket_lead_notify_cc', ''));
        $this->assertSame('', (string) SiteSetting::get(SiteSetting::PORTALY_STORE_SLUG_KEY, ''));
        $this->assertSame('', (string) SiteSetting::get(SiteIconService::LOGO_KEY, ''));
    }

    public function test_an_existing_install_keeps_what_it_used_to_show(): void
    {
        Storage::fake('public');
        SiteSetting::set('hero_title', '經營者時間銀行');

        $this->runMigration();

        $this->assertSame('themustbig+learn@gmail.com', SiteSetting::get('support_email'));
        $this->assertSame('themustbig+leads@gmail.com', SiteSetting::get('high_ticket_lead_notify_cc'));
        $this->assertSame('kyontw', SiteSetting::get(SiteSetting::PORTALY_STORE_SLUG_KEY));
        $this->assertSame('site-icons/legacy-logo.png', SiteSetting::get(SiteIconService::LOGO_KEY));
        Storage::disk('public')->assertExists('site-icons/legacy-logo.png');
    }

    public function test_values_the_admin_already_set_are_left_alone(): void
    {
        Storage::fake('public');
        SiteSetting::set('hero_title', '經營者時間銀行');
        SiteSetting::set('support_email', 'help@example.com');

        $this->runMigration();

        $this->assertSame('help@example.com', SiteSetting::get('support_email'));
    }

    public function test_portaly_url_needs_both_a_store_and_a_product(): void
    {
        $this->assertNull(SiteSetting::portalyProductUrl('abc'));

        SiteSetting::set(SiteSetting::PORTALY_STORE_SLUG_KEY, 'mystore');

        $this->assertNull(SiteSetting::portalyProductUrl(null));
        $this->assertSame('https://portaly.cc/mystore/product/abc', SiteSetting::portalyProductUrl('abc'));
    }
}
