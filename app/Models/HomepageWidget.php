<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * One block of the homepage — built-in or admin-authored HTML (002 US23).
 *
 * Both kinds share this table because they share one `sort_order` axis: a
 * custom block has to be able to sit between two built-in ones (002 D65).
 */
class HomepageWidget extends Model
{
    public const TYPE_BUILTIN = 'builtin';

    public const TYPE_HTML = 'html';

    public const AREA_MAIN = 'main';

    public const AREA_SIDE = 'side';

    public const AREAS = [self::AREA_MAIN, self::AREA_SIDE];

    protected $fillable = ['key', 'type', 'area', 'title', 'html', 'sort_order', 'is_visible'];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_visible' => 'boolean',
        ];
    }

    public function scopeArea(Builder $query, string $area): Builder
    {
        return $query->where('area', $area);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        // `id` breaks ties so a page's widget order never depends on row order
        // coming back from the driver — two widgets can share a sort_order
        // while a reorder is half applied.
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true);
    }

    /** Built-ins may only have `is_visible` and `sort_order` written (002 FR-074). */
    public function isBuiltin(): bool
    {
        return $this->type === self::TYPE_BUILTIN;
    }
}
