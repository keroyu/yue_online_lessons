<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class SiteSetting extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * The public-facing contact address (011 FR-057).
     *
     * Distinct from `high_ticket_lead_notify_cc`, which is "who picks this lead
     * up" — this one is "where a visitor writes when something goes wrong", and
     * it is the address that appears in system mail and in email templates via
     * `{{support_email}}`. Kept here rather than in a feature service because
     * more than one module needs to print it.
     */
    public const SUPPORT_EMAIL_KEY = 'support_email';

    public const DEFAULT_SUPPORT_EMAIL = 'themustbig+learn@gmail.com';

    public static function supportEmail(): string
    {
        return trim((string) static::get(self::SUPPORT_EMAIL_KEY, '')) ?: self::DEFAULT_SUPPORT_EMAIL;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return static::where('key', $key)->value('value') ?? $default;
    }

    public static function getMany(array $keys): Collection
    {
        return static::whereIn('key', $keys)->pluck('value', 'key');
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
