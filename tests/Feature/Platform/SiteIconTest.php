<?php

namespace Tests\Feature\Platform;

use App\Models\SiteSetting;
use App\Models\User;
use App\Services\SiteIconService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * 000 US12 — the navbar logo and the favicon are uploads, not repo assets.
 *
 * The favicon is the interesting half: the admin uploads one PNG and the app
 * derives every size a client asks for, including a real .ico, because
 * `/favicon.ico` is fetched by name whether the page declares an icon or not.
 */
class SiteIconTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create(['email' => 'admin@example.com', 'role' => 'admin']);
    }

    /** A real PNG with an alpha channel, so the transparency path is exercised. */
    private function png(int $size = 200, array $rgb = [255, 0, 0]): UploadedFile
    {
        $im = imagecreatetruecolor($size, $size);
        imagealphablending($im, false);
        imagesavealpha($im, true);
        imagefill($im, 0, 0, imagecolorallocatealpha($im, $rgb[0], $rgb[1], $rgb[2], 40));

        $file = tempnam(sys_get_temp_dir(), 'icon') . '.png';
        imagepng($im, $file);
        imagedestroy($im);

        return new UploadedFile($file, 'logo.png', 'image/png', null, true);
    }

    public function test_logo_upload_is_stored_and_shared_with_every_page(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())
            ->post('/admin/homepage/site-identity', [
                'site_name' => '測試站台',
                'site_logo' => $this->png(),
            ])
            ->assertRedirect();

        $path = SiteSetting::get('site_logo_path');
        $this->assertNotEmpty($path);
        Storage::disk('public')->assertExists($path);

        $this->get('/')->assertInertia(fn ($page) => $page->where('site.logoUrl', Storage::disk('public')->url($path)));
    }

    public function test_one_uploaded_png_produces_every_favicon_size(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())
            ->post('/admin/homepage/site-identity', [
                'site_name'    => '測試站台',
                'site_favicon' => $this->png(),
            ])
            ->assertRedirect();

        $disk = Storage::disk('public');

        foreach (['site_favicon_path', 'site_favicon_apple_path', 'site_favicon_ico_path'] as $key) {
            $path = SiteSetting::get($key);
            $this->assertNotEmpty($path, "$key 應該被填上");
            $disk->assertExists($path);
        }

        [$w, $h] = getimagesizefromstring($disk->get(SiteSetting::get('site_favicon_path')));
        $this->assertSame([32, 32], [$w, $h]);

        [$aw, $ah] = getimagesizefromstring($disk->get(SiteSetting::get('site_favicon_apple_path')));
        $this->assertSame([180, 180], [$aw, $ah]);
    }

    public function test_the_generated_ico_is_a_real_ico_wrapping_a_png(): void
    {
        Storage::fake('public');

        app(SiteIconService::class)->storeFavicon($this->png());

        $ico = Storage::disk('public')->get(SiteSetting::get('site_favicon_ico_path'));

        // Header: reserved 0, type 1 (icon), one image.
        $header = unpack('vreserved/vtype/vcount', substr($ico, 0, 6));
        $this->assertSame(0, $header['reserved']);
        $this->assertSame(1, $header['type']);
        $this->assertSame(1, $header['count']);

        // Directory entry: 32x32, 32bpp, and the payload starts right after it.
        $entry = unpack('Cwidth/Cheight/Ccolors/Creserved/vplanes/vbpp/Vbytes/Voffset', substr($ico, 6, 16));
        $this->assertSame(32, $entry['width']);
        $this->assertSame(32, $entry['height']);
        $this->assertSame(32, $entry['bpp']);
        $this->assertSame(22, $entry['offset']);
        $this->assertSame(strlen($ico) - 22, $entry['bytes']);

        // The payload really is a PNG (that is what makes this file legal).
        $this->assertStringStartsWith("\x89PNG", substr($ico, 22));
    }

    public function test_favicon_route_serves_the_ico_and_404s_without_one(): void
    {
        Storage::fake('public');

        $this->get('/favicon.ico')->assertNotFound();

        app(SiteIconService::class)->storeFavicon($this->png());

        $this->get('/favicon.ico')
            ->assertOk()
            ->assertHeader('Content-Type', 'image/x-icon');
    }

    public function test_layout_declares_the_icons_only_once_uploaded(): void
    {
        Storage::fake('public');

        $this->get('/')->assertDontSee('apple-touch-icon', false);

        app(SiteIconService::class)->storeFavicon($this->png());

        $this->get('/')
            ->assertSee('rel="apple-touch-icon"', false)
            ->assertSee('type="image/png"', false);
    }

    public function test_replacing_an_icon_removes_the_previous_files(): void
    {
        Storage::fake('public');

        $icons = app(SiteIconService::class);
        $icons->storeFavicon($this->png());
        $first = SiteSetting::get('site_favicon_ico_path');

        // A different colour, not just a different source size: the filename is
        // derived from the generated 32x32 bytes, and two solid squares scale
        // down to the same thing.
        $icons->storeFavicon($this->png(120, [0, 0, 255]));

        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists(SiteSetting::get('site_favicon_ico_path'));
    }

    public function test_deleting_clears_both_the_files_and_the_settings(): void
    {
        Storage::fake('public');

        $icons = app(SiteIconService::class);
        $icons->storeLogo($this->png());
        $icons->storeFavicon($this->png());
        $logo = SiteSetting::get('site_logo_path');
        $ico = SiteSetting::get('site_favicon_ico_path');

        $admin = $this->admin();
        $this->actingAs($admin)->delete('/admin/homepage/site-logo')->assertRedirect();
        $this->actingAs($admin)->delete('/admin/homepage/site-favicon')->assertRedirect();

        Storage::disk('public')->assertMissing($logo);
        Storage::disk('public')->assertMissing($ico);
        $this->assertSame('', SiteSetting::get('site_logo_path'));
        $this->assertSame('', SiteSetting::get('site_favicon_ico_path'));
    }

    public function test_guests_cannot_upload_icons(): void
    {
        Storage::fake('public');

        $this->post('/admin/homepage/site-identity', [
            'site_name' => '駭客站',
            'site_logo' => $this->png(),
        ])->assertRedirect('/login');

        $this->assertNull(SiteSetting::get('site_logo_path'));
    }

    public function test_a_non_image_is_rejected(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())
            ->from('/admin/homepage')
            ->post('/admin/homepage/site-identity', [
                'site_name' => '測試站台',
                'site_logo' => UploadedFile::fake()->create('evil.svg', 10, 'image/svg+xml'),
            ])
            ->assertSessionHasErrors('site_logo');

        $this->assertNull(SiteSetting::get('site_logo_path'));
    }
}
