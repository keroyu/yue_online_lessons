<?php

namespace Tests\Feature\Platform;

use App\Models\SiteSetting;
use App\Models\User;
use App\Services\ThemeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 000 US14 — the whole site's palette is eight named schemes, switchable from
 * the back office without a rebuild.
 *
 * The contrast block below is the point of this file. Those nine ratios are an
 * admission requirement for a scheme (FR-121 / D46), not advice: the palette
 * this site ran on for over a year had four of them failing, the worst being
 * instalment prices at 1.77:1 — unreadable, and invisible to anyone eyeballing
 * it, because a saturated orange *looks* clear on cream without being it.
 */
class ColorSchemeTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create(['email' => 'admin@example.com', 'role' => 'admin']);
    }

    // ---------------------------------------------------------------- WCAG --

    /** WCAG 2.1 relative luminance of an #RRGGBB string. */
    private function luminance(string $hex): float
    {
        $channels = array_map(
            fn ($c) => ($c /= 255) <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4,
            sscanf(ltrim($hex, '#'), '%2x%2x%2x')
        );

        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    }

    private function contrast(string $a, string $b): float
    {
        $la = $this->luminance($a);
        $lb = $this->luminance($b);

        return (max($la, $lb) + 0.05) / (min($la, $lb) + 0.05);
    }

    /**
     * [label, foreground role, background role, minimum ratio] (FR-121).
     *
     * Prices are `text-xl`–`text-3xl` bold, which WCAG counts as large text at
     * 3:1; everything else is normal text at 4.5:1.
     *
     * Body ink was held to AAA 7:1 at first. That turned out to be the reason
     * three schemes came out heavy: `navy` is both the body ink AND the navbar
     * fill, so pushing it to 7:1 drove it to near-black, and with the primary
     * button also a dark fill the page read as two black slabs. AA 4.5 is the
     * standard the rest of the palette is held to, and it leaves `navy` room to
     * be a mid-tone.
     */
    private static function gates(): array
    {
        return [
            ['內文對頁面底色', 'navy', 'cream', 4.5],
            ['導覽列與後台側欄', '#FFFFFF', 'navy', 4.5],
            ['主要按鈕', '#FFFFFF', 'teal', 4.5],
            ['連結', 'teal', 'cream', 4.5],
            ['強調 CTA', 'navy', 'gold', 4.5],
            ['強調 CTA 的 hover', 'navy', 'gold_dark', 4.5],
            ['促銷徽章', '#FFFFFF', 'red', 4.5],
            ['售價（大型粗體）', 'red', 'cream', 3.0],
            ['分期價（大型粗體）', 'orange', 'cream', 3.0],
            // The navbar fill and the primary button fill must not read as the
            // same slab — the complaint that prompted the relaxation above.
            ['導覽列與主要按鈕的區辨', 'navy', 'teal', 1.35],
        ];
    }

    // --------------------------------------------------------------- Shape --

    public function test_every_scheme_declares_all_seven_roles_as_hex(): void
    {
        $schemes = config('themes.schemes');

        $this->assertCount(8, $schemes, '應有八組配色');

        foreach ($schemes as $key => $scheme) {
            $this->assertIsString($scheme['name'] ?? null, "{$key} 缺少 name");
            $this->assertIsString($scheme['industry'] ?? null, "{$key} 缺少 industry");

            $this->assertSame(
                ThemeService::ROLES,
                array_keys($scheme['colors']),
                "{$key} 的色票鍵必須恰好是七個角色且順序一致"
            );

            foreach ($scheme['colors'] as $role => $hex) {
                $this->assertMatchesRegularExpression(
                    '/^#[0-9A-F]{6}$/',
                    $hex,
                    "{$key}.{$role} 必須是大寫的 6 位 hex"
                );
            }
        }
    }

    public function test_the_default_scheme_exists(): void
    {
        $this->assertArrayHasKey(config('themes.default'), config('themes.schemes'));
    }

    // ------------------------------------------------------------ Contrast --

    public function test_every_scheme_passes_every_contrast_gate(): void
    {
        $failures = [];

        foreach (config('themes.schemes') as $key => $scheme) {
            $colors = $scheme['colors'];

            foreach (self::gates() as [$label, $fg, $bg, $min]) {
                $ratio = $this->contrast(
                    str_starts_with($fg, '#') ? $fg : $colors[$fg],
                    str_starts_with($bg, '#') ? $bg : $colors[$bg],
                );

                if ($ratio < $min) {
                    $failures[] = sprintf('%s / %s：%.2f < %.1f', $key, $label, $ratio, $min);
                }
            }
        }

        $this->assertSame([], $failures, "以下配色未達對比門檻：\n".implode("\n", $failures));
    }

    /**
     * All schemes are light (FR-120): `cream` is the body background and `navy`
     * is the admin sidebar carrying white text, so the two roles are locked in
     * opposite directions. A dark scheme would have to swap them.
     */
    public function test_every_scheme_is_light_canvas_over_dark_ink(): void
    {
        foreach (config('themes.schemes') as $key => $scheme) {
            $this->assertGreaterThan(
                0.6,
                $this->luminance($scheme['colors']['cream']),
                "{$key} 的 cream 必須是亮色（頁面底色）"
            );
            $this->assertLessThan(
                0.2,
                $this->luminance($scheme['colors']['navy']),
                "{$key} 的 navy 必須是暗色（配白字的導覽列與側欄）"
            );
        }
    }

    /** The hover fill must actually read as darker than what it replaces. */
    public function test_gold_dark_is_darker_than_gold(): void
    {
        foreach (config('themes.schemes') as $key => $scheme) {
            $this->assertGreaterThan(
                $this->luminance($scheme['colors']['gold_dark']) * 1.25,
                $this->luminance($scheme['colors']['gold']),
                "{$key} 的 gold-dark 必須明顯暗於 gold"
            );
        }
    }

    // -------------------------------------------------------------- Active --

    public function test_active_scheme_defaults_to_cream_indigo(): void
    {
        $this->assertSame('cream-indigo', app(ThemeService::class)->active()['key']);
    }

    public function test_active_scheme_follows_the_setting(): void
    {
        SiteSetting::set('color_scheme', 'terracotta');

        $active = app(ThemeService::class)->active();

        $this->assertSame('terracotta', $active['key']);
        $this->assertSame('#A34A32', $active['colors']['teal']);
    }

    /**
     * A scheme may be retired in a later release while a site still has it
     * selected. That must not 500 every page on the site.
     */
    public function test_unknown_scheme_in_the_database_falls_back_to_the_default(): void
    {
        SiteSetting::set('color_scheme', 'scheme-that-was-removed');

        $this->assertSame('cream-indigo', app(ThemeService::class)->active()['key']);
    }

    // -------------------------------------------------------------- Render --

    public function test_the_homepage_carries_the_active_palette(): void
    {
        SiteSetting::set('color_scheme', 'deep-harbor');

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('--color-brand-teal: #2E5F8A', $html);
        $this->assertStringNotContainsString('#33697F', $html, '不應再出現預設配色的 teal');
    }

    /**
     * Our `:root` and Tailwind's `:root,:host` have identical specificity, so
     * the winner is decided by source order alone (FR-123). Emitted before the
     * bundle, the override silently does nothing — and that looks exactly like
     * a save that did not go through.
     */
    public function test_the_override_is_emitted_after_the_stylesheet(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $override = strpos($html, '--color-brand-navy');
        $this->assertNotFalse($override, '<head> 應輸出配色覆寫');

        $this->assertMatchesRegularExpression('/<link[^>]+rel="stylesheet"[^>]+\.css/', $html);
        preg_match_all('/<link[^>]+href="([^"]+\.css)"/', substr($html, 0, $override), $before);

        $this->assertNotEmpty($before[1], '配色覆寫必須排在 vite 的 CSS link 之後');
    }

    // ---------------------------------------------------------------- Save --

    public function test_admin_can_switch_the_scheme(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/homepage/color-scheme', ['color_scheme' => 'moss-field'])
            ->assertRedirect();

        $this->assertSame('moss-field', SiteSetting::get('color_scheme'));
    }

    /** The picker is a card on the homepage settings page, not a page of its own (D47). */
    public function test_the_homepage_settings_page_carries_the_picker(): void
    {
        SiteSetting::set('color_scheme', 'ink-bamboo');

        $this->actingAs($this->admin())
            ->get('/admin/homepage')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('activeColorScheme', 'ink-bamboo')
                ->has('colorSchemes', 8)
                ->where('colorSchemes.0.key', 'cream-indigo')
                ->has('colorSchemes.0.colors.gold_dark'));
    }

    public function test_an_unknown_scheme_is_rejected(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/homepage/color-scheme', ['color_scheme' => 'neon-disaster'])
            ->assertSessionHasErrors('color_scheme');

        $this->assertNull(SiteSetting::get('color_scheme'));
    }

    public function test_guests_and_consultants_cannot_switch_the_scheme(): void
    {
        $this->post('/admin/homepage/color-scheme', ['color_scheme' => 'terracotta'])
            ->assertRedirect('/login');

        $consultant = User::create([
            'email'               => 'consultant@example.com',
            'role'                => 'member',
            'is_sales_consultant' => true,
        ]);

        // AdminMiddleware sends non-admins home with a flash rather than
        // returning 403 — the convention SalesConsultantTest already pins.
        $this->actingAs($consultant)
            ->post('/admin/homepage/color-scheme', ['color_scheme' => 'terracotta'])
            ->assertRedirect('/');

        $this->assertNull(SiteSetting::get('color_scheme'));
    }

    /**
     * The homepage settings page saves each of its cards separately; picking a
     * palette must not wipe the site name sitting in the card above it.
     */
    public function test_saving_the_scheme_leaves_the_other_cards_alone(): void
    {
        SiteSetting::set('site_name', '某某學院');
        SiteSetting::set('hero_title', '歡迎');
        SiteSetting::set('content_filter_enabled', '1');

        $this->actingAs($this->admin())
            ->post('/admin/homepage/color-scheme', ['color_scheme' => 'rose-quartz'])
            ->assertRedirect();

        $this->assertSame('某某學院', SiteSetting::get('site_name'));
        $this->assertSame('歡迎', SiteSetting::get('hero_title'));
        $this->assertSame('1', SiteSetting::get('content_filter_enabled'));
    }

    // -------------------------------------------------------------- Source --

    /**
     * A setting only half the code respects is worse than no setting (the same
     * guard FR-117 puts on the brand name). `LessonForm.vue` is the one file
     * exempt: the CTA it builds is inline-styled HTML saved into lesson bodies
     * and mailed out by the drip sequence, and mail clients do not resolve CSS
     * variables.
     */
    public function test_no_brand_hex_is_hardcoded_in_the_front_end(): void
    {
        $exempt = 'resources/js/Components/Admin/LessonForm.vue';

        $palette = [];
        foreach (config('themes.schemes') as $scheme) {
            foreach ($scheme['colors'] as $hex) {
                $palette[] = strtolower(ltrim($hex, '#'));
            }
        }
        // The pre-US14 values, which is what a copy-paste would reintroduce.
        $palette = array_unique(array_merge($palette, [
            'f6f1e9', 'faa45e', 'ff4438', '373557', '3f83a3', 'f0c14b', 'c7a33b', '336d8a',
        ]));

        $offenders = [];

        foreach (['resources/css', 'resources/js'] as $dir) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(base_path($dir), \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($files as $file) {
                $relative = str_replace(base_path().'/', '', $file->getPathname());

                if ($relative === $exempt || ! in_array($file->getExtension(), ['css', 'vue', 'js'], true)) {
                    continue;
                }

                $contents = strtolower(file_get_contents($file->getPathname()));

                // The `@theme` block is where the default palette is *defined*,
                // so literals there are the point rather than a bypass. Strip it
                // and hold the rest of the file to the rule.
                $contents = preg_replace('/@theme\s*\{[^}]*\}/s', '', $contents);

                foreach ($palette as $hex) {
                    if (str_contains($contents, '#'.$hex)) {
                        $offenders[] = "{$relative} → #{$hex}";
                    }

                    // The same bypass spelled decimal. Three teal call sites
                    // were hiding as rgba(63,131,163,…) and sailed past a
                    // hex-only scan — which is exactly the silent half-applied
                    // setting this guard exists to prevent.
                    [$r, $g, $b] = sscanf($hex, '%2x%2x%2x');
                    if (preg_match("/rgba?\\(\\s*{$r}\\s*,\\s*{$g}\\s*,\\s*{$b}\\s*[,)]/", $contents)) {
                        $offenders[] = "{$relative} → rgb({$r},{$g},{$b})";
                    }
                }
            }
        }

        $this->assertSame([], array_unique($offenders), "以下檔案仍寫死品牌色：\n".implode("\n", array_unique($offenders)));
    }
}
