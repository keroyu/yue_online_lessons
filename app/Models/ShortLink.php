<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;

class ShortLink extends Model
{
    protected $fillable = ['slug', 'target_url', 'name', 'is_active', 'clicks', 'last_clicked_at'];

    protected function casts(): array
    {
        return [
            'is_active'       => 'boolean',
            'clicks'          => 'integer',
            'last_clicked_at' => 'datetime',
        ];
    }

    /**
     * Slugs are always stored lowercase so lookups are case-insensitive
     * regardless of the database collation.
     */
    protected function slug(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => $value === null ? null : mb_strtolower(trim($value)),
        );
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Atomic counter bump — never recalculated, see spec D17.
     */
    public function recordClick(): void
    {
        $this->increment('clicks', 1, ['last_clicked_at' => now()]);
    }

    /**
     * A slug may not shadow the first segment of any registered route.
     * Derived from the route collection (spec D15) so new features are
     * protected automatically, with no blacklist to maintain.
     *
     * @return array<int, string>
     */
    public static function reservedSlugs(): array
    {
        $reserved = [];

        foreach (Route::getRoutes() as $route) {
            $first = explode('/', trim($route->uri(), '/'))[0] ?? '';

            // Skip the catch-all itself and any route starting with a parameter
            if ($first === '' || str_contains($first, '{')) {
                continue;
            }

            $reserved[mb_strtolower($first)] = true;
        }

        // Public directories served by the web server before Laravel routing
        foreach (['storage', 'build', 'images', 'fonts', 'css', 'js'] as $path) {
            $reserved[$path] = true;
        }

        return array_keys($reserved);
    }

    public static function isReservedSlug(string $slug): bool
    {
        return in_array(mb_strtolower(trim($slug)), static::reservedSlugs(), true);
    }
}
