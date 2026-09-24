<?php

namespace App\Services;

use App\Models\SiteSetting;

/**
 * The site's colour scheme (000 US14).
 *
 * A scheme is nothing but new values for the seven `--color-brand-*` custom
 * properties. Tailwind v4 compiles every opaque brand utility down to
 * `var(--color-brand-…)`, so redefining those seven variables at runtime
 * recolours all 1,099 call sites at once — no rebuild, no class switching, and
 * none of the 90 files that use them has to change (D41).
 *
 * The schemes themselves live in `config/themes.php` because they are a design
 * asset that belongs in git; the database only remembers which one is on (D43).
 */
class ThemeService
{
    public const SETTING_KEY = 'color_scheme';

    /**
     * The seven roles, in the order a scheme must declare them.
     *
     * These are roles, not hues: `teal` is burnt sienna in Terracotta. They
     * keep their original names because renaming them means 1,099 call sites,
     * and a missed one fails silently rather than loudly (D44).
     */
    public const ROLES = ['cream', 'navy', 'teal', 'gold', 'gold_dark', 'orange', 'red'];

    /** Every scheme, keyed for the admin picker. */
    public function all(): array
    {
        $schemes = [];

        foreach (config('themes.schemes', []) as $key => $scheme) {
            $schemes[] = [
                'key'      => $key,
                'name'     => $scheme['name'],
                'industry' => $scheme['industry'],
                'colors'   => $scheme['colors'],
            ];
        }

        return $schemes;
    }

    /**
     * The scheme in force.
     *
     * Deliberately uncached, including no static memo: a queue worker's static
     * state survives across jobs, so a cached palette would keep serving the
     * old colours in mail and generated images until the worker restarted —
     * the same reasoning `SiteSetting::identity()` already follows (FR-113).
     * The cost is one indexed read on a tiny table.
     *
     * An unknown key falls back to the default rather than throwing: a scheme
     * can be retired in a later release, and the stale value left behind in
     * someone's database must not take every page down with it (FR-119).
     */
    public function active(): array
    {
        $key = trim((string) SiteSetting::get(self::SETTING_KEY, ''));

        return $this->scheme($key);
    }

    public function activeKey(): string
    {
        return $this->active()['key'];
    }

    /** A named scheme, or the default when the name is unknown or blank. */
    public function scheme(string $key): array
    {
        $schemes = config('themes.schemes', []);

        if (! isset($schemes[$key])) {
            $key = (string) config('themes.default');
        }

        return [
            'key'      => $key,
            'name'     => $schemes[$key]['name'],
            'industry' => $schemes[$key]['industry'],
            'colors'   => $schemes[$key]['colors'],
        ];
    }

    /**
     * The `:root` block that overrides the compiled defaults.
     *
     * Must be emitted AFTER the Vite stylesheet. Our `:root` and Tailwind's
     * `:root,:host` have the same specificity, so source order alone decides
     * the winner — placed before the bundle this silently does nothing, which
     * on screen is indistinguishable from a save that failed (FR-123).
     */
    public function cssVariables(): string
    {
        $declarations = [];

        foreach ($this->active()['colors'] as $role => $hex) {
            $declarations[] = sprintf('--color-brand-%s: %s;', str_replace('_', '-', $role), $hex);
        }

        return ':root{'.implode('', $declarations).'}';
    }
}
