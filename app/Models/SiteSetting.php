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

    /**
     * The Portaly storefront handle — the `{slug}` in
     * `https://portaly.cc/{slug}/product/{id}`. Each install sells from its
     * own store, so it is a setting rather than part of the URL template.
     */
    public const PORTALY_STORE_SLUG_KEY = 'portaly_store_slug';

    /** Purchase page for a Portaly product, or null when the store is unset. */
    public static function portalyProductUrl(?string $productId): ?string
    {
        $slug = trim((string) static::get(self::PORTALY_STORE_SLUG_KEY, ''));

        if ($slug === '' || ! $productId) {
            return null;
        }

        return 'https://portaly.cc/' . rawurlencode($slug) . '/product/' . rawurlencode($productId);
    }

    public static function supportEmail(): string
    {
        return trim((string) static::get(self::SUPPORT_EMAIL_KEY, '')) ?: static::defaultSupportEmail();
    }

    /**
     * What an unconfigured install prints: the earliest administrator, else
     * the sender address. Derived rather than a constant because every install
     * of this codebase belongs to a different owner.
     */
    public static function defaultSupportEmail(): string
    {
        return (string) (User::where('role', 'admin')->orderBy('id')->value('email')
            ?: config('mail.from.address', ''));
    }

    /**
     * Site identity: display name, legal operator, registered address.
     *
     * These used to be literals scattered across the navbar, the footer, the
     * legal modal, the OG card and half a dozen mail templates, which made the
     * codebase unusable for a second install. They live here because the legal
     * modal renders from the footer — there is no controller to pass them from —
     * and because mail templates need them outside any HTTP request.
     */
    public const SITE_NAME_KEY = 'site_name';

    public const SITE_OPERATOR_KEY = 'site_operator';

    public const SITE_ADDRESS_KEY = 'site_address';

    public static function siteName(): string
    {
        $identity = static::identity();

        return $identity['name'];
    }

    public static function siteOperator(): string
    {
        return static::identity()['operator'];
    }

    public static function siteAddress(): string
    {
        return static::identity()['address'];
    }

    /**
     * The three identity values in one read — the Inertia middleware shares all
     * of them on every request, so this is one query rather than three.
     *
     * `hero_title` is the fallback for the name because it was the closest thing
     * to a site name before this key existed; installs that predate the migration
     * keep showing what they showed yesterday. `config('app.name')` backs that up so
     * a brand-new database still renders something rather than an empty navbar.
     */
    public static function identity(): array
    {
        $values = static::getMany([
            self::SITE_NAME_KEY,
            'hero_title',
            self::SITE_OPERATOR_KEY,
            self::SITE_ADDRESS_KEY,
        ]);

        $name = trim((string) $values->get(self::SITE_NAME_KEY, ''))
            ?: trim((string) $values->get('hero_title', ''))
            ?: trim((string) config('app.name', ''));

        return [
            'name'     => $name,
            'operator' => trim((string) $values->get(self::SITE_OPERATOR_KEY, '')),
            'address'  => trim((string) $values->get(self::SITE_ADDRESS_KEY, '')),
        ];
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
