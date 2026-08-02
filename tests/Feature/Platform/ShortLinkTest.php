<?php

namespace Tests\Feature\Platform;

use App\Models\ShortLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShortLinkTest extends TestCase
{
    use RefreshDatabase;

    private function makeLink(array $overrides = []): ShortLink
    {
        return ShortLink::create(array_merge([
            'slug'       => '1v1',
            'target_url' => 'https://calendar.app.google/abc123',
            'name'       => '1對1諮詢預約',
            'is_active'  => true,
        ], $overrides));
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    // ── 前台轉址 ──

    public function test_active_short_link_redirects_and_counts_click(): void
    {
        $link = $this->makeLink();

        $res = $this->get('/1v1');

        $res->assertStatus(302);
        $res->assertRedirect('https://calendar.app.google/abc123');
        $this->assertStringContainsString('no-store', $res->headers->get('Cache-Control'));

        $link->refresh();
        $this->assertSame(1, $link->clicks);
        $this->assertNotNull($link->last_clicked_at);
    }

    public function test_slug_lookup_is_case_insensitive(): void
    {
        $this->makeLink();

        $this->get('/1V1')->assertRedirect('https://calendar.app.google/abc123');
    }

    public function test_slug_is_normalised_to_lowercase_on_save(): void
    {
        $link = $this->makeLink(['slug' => 'MyLink']);

        $this->assertSame('mylink', $link->fresh()->slug);
    }

    public function test_inactive_short_link_returns_404(): void
    {
        $this->makeLink(['is_active' => false]);

        $this->get('/1v1')->assertNotFound();
    }

    public function test_unknown_slug_returns_404(): void
    {
        $this->get('/nothing-here')->assertNotFound();
    }

    public function test_catch_all_does_not_swallow_existing_routes(): void
    {
        // Registered routes must always win over the catch-all
        $this->get('/blog')->assertOk();
        $this->get('/cart')->assertOk();
        $this->get('/admin')->assertRedirect('/login'); // auth middleware, not a 404 from the catch-all
    }

    // ── 後台 CRUD ──

    public function test_admin_can_list_short_links(): void
    {
        $this->makeLink();

        $this->actingAs($this->admin())->get('/admin/short-links')->assertOk();
    }

    public function test_last_click_time_is_shown_in_taipei_time(): void
    {
        // Stored in UTC; Taipei is UTC+8, so this must read back as 09:30 next day
        $link = $this->makeLink(['last_clicked_at' => '2026-08-02 01:30:00']);

        $this->actingAs($this->admin())
            ->get('/admin/short-links')
            ->assertInertia(fn ($page) => $page
                ->where('links.0.last_clicked_at', '2026-08-02 09:30'));

        $this->assertSame('2026-08-02 01:30:00', $link->fresh()->last_clicked_at->utc()->toDateTimeString());
    }

    public function test_non_admin_cannot_access_short_links(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/short-links')
            ->assertRedirect('/');
    }

    public function test_admin_can_create_short_link(): void
    {
        $this->actingAs($this->admin())->post('/admin/short-links', [
            'slug'       => 'Webinar-2026',
            'target_url' => 'https://zoom.us/j/123',
            'name'       => '線上講座',
            'is_active'  => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('short_links', [
            'slug'       => 'webinar-2026',
            'target_url' => 'https://zoom.us/j/123',
        ]);
    }

    public function test_reserved_slug_is_rejected(): void
    {
        $this->actingAs($this->admin())->post('/admin/short-links', [
            'slug'       => 'blog',
            'target_url' => 'https://example.com',
        ])->assertSessionHasErrors('slug');

        $this->assertDatabaseCount('short_links', 0);
    }

    public function test_duplicate_slug_is_rejected(): void
    {
        $this->makeLink();

        $this->actingAs($this->admin())->post('/admin/short-links', [
            'slug'       => '1V1',
            'target_url' => 'https://example.com',
        ])->assertSessionHasErrors('slug');
    }

    public function test_non_http_target_url_is_rejected(): void
    {
        $this->actingAs($this->admin())->post('/admin/short-links', [
            'slug'       => 'evil',
            'target_url' => 'javascript:alert(1)',
        ])->assertSessionHasErrors('target_url');
    }

    public function test_admin_can_update_target_url_keeping_clicks(): void
    {
        $link = $this->makeLink(['clicks' => 42]);

        $this->actingAs($this->admin())->put("/admin/short-links/{$link->id}", [
            'slug'       => '1v1',
            'target_url' => 'https://calendar.app.google/new-staff-link',
            'name'       => '1對1諮詢預約（小美）',
            'is_active'  => true,
        ])->assertRedirect();

        $link->refresh();
        $this->assertSame('https://calendar.app.google/new-staff-link', $link->target_url);
        $this->assertSame(42, $link->clicks);
    }

    public function test_updating_a_link_can_keep_its_own_slug(): void
    {
        $link = $this->makeLink();

        $this->actingAs($this->admin())->put("/admin/short-links/{$link->id}", [
            'slug'       => '1v1',
            'target_url' => 'https://example.com',
            'is_active'  => true,
        ])->assertSessionHasNoErrors();
    }

    public function test_admin_can_delete_short_link(): void
    {
        $link = $this->makeLink();

        $this->actingAs($this->admin())
            ->delete("/admin/short-links/{$link->id}")
            ->assertRedirect();

        $this->assertDatabaseCount('short_links', 0);
    }
}
