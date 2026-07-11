<?php

namespace Tests\Feature\Storefront;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * US9 — 站長形象圖片與介紹（site_settings KV，無 migration）。
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

    public function test_image_upload_stores_and_replacing_deletes_old(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/homepage', [
            'sns_section_enabled' => 1,
            'sns_profile_image'   => UploadedFile::fake()->image('avatar.jpg'),
        ])->assertRedirect();

        $first = SiteSetting::get('sns_profile_image_path');
        $this->assertNotNull($first);
        Storage::disk('public')->assertExists($first);

        // Replace → new path stored, old file deleted.
        $this->actingAs($admin)->post('/admin/homepage', [
            'sns_section_enabled' => 1,
            'sns_profile_image'   => UploadedFile::fake()->image('avatar2.jpg'),
        ])->assertRedirect();

        $second = SiteSetting::get('sns_profile_image_path');
        $this->assertNotSame($first, $second);
        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($second);
    }

    public function test_delete_image_route_removes_file_and_path(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/homepage', [
            'sns_section_enabled' => 1,
            'sns_profile_image'   => UploadedFile::fake()->image('avatar.jpg'),
        ]);
        $path = SiteSetting::get('sns_profile_image_path');
        $this->assertNotNull($path);

        $this->actingAs($admin)->delete('/admin/homepage/sns-profile-image')->assertRedirect();

        $this->assertNull(SiteSetting::get('sns_profile_image_path'));
        Storage::disk('public')->assertMissing($path);
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
