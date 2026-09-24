<?php

namespace Tests\Feature\Platform;

use Tests\TestCase;

/**
 * 000 US2 — the admin chrome is the sidebar. There is no top bar on desktop.
 *
 * The bar that used to live there held nothing but 登出 and a mobile hamburger,
 * and because it was `sticky top-0` across the full content column, every admin
 * page scrolled its content underneath a white band. It has come back once
 * already, so this is a source-level guard rather than a note in a spec:
 * anything pinned to the top of the content column must be `lg:hidden`.
 */
class AdminLayoutChromeTest extends TestCase
{
    private function layoutSource(): string
    {
        return (string) file_get_contents(resource_path('js/Layouts/AdminLayout.vue'));
    }

    public function test_no_top_bar_is_visible_on_desktop(): void
    {
        $offenders = [];

        // Every element pinned to the top of the content column.
        preg_match_all('/class="([^"]*\bsticky\b[^"]*\btop-0\b[^"]*)"/', $this->layoutSource(), $matches);

        foreach ($matches[1] as $classList) {
            if (! str_contains($classList, 'lg:hidden')) {
                $offenders[] = $classList;
            }
        }

        $this->assertSame(
            [],
            $offenders,
            '後台桌機版不應有置頂橫條；手機版的漢堡列請加 lg:hidden'
        );
    }

    public function test_logout_lives_in_the_sidebar_on_both_breakpoints(): void
    {
        // Two copies: the desktop sidebar footer and the mobile drawer footer.
        // Fewer than two means one breakpoint has no way to log out at all.
        $this->assertSame(
            2,
            substr_count($this->layoutSource(), 'href="/logout"'),
            '登出必須同時存在於桌機側欄與手機抽屜的底部'
        );
    }
}
