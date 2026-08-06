<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class EmailSuppression extends Model
{
    protected $fillable = ['email', 'reason', 'detail', 'suppressed_at'];

    protected function casts(): array
    {
        return [
            'suppressed_at' => 'datetime',
        ];
    }

    /**
     * Always stored lowercase — email is the shared key across members, leads
     * and CC recipients, and lookups must not depend on how it was typed in.
     */
    protected function email(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => $value === null ? null : mb_strtolower(trim($value)),
        );
    }

    public static function reasonFor(string $email): ?string
    {
        return static::where('email', mb_strtolower(trim($email)))->value('reason');
    }

    /**
     * Bulk lookup for admin list pages — one query instead of one per row.
     *
     * @param  iterable<string>  $emails
     * @return \Illuminate\Support\Collection<string, string> keyed by lowercased email
     */
    public static function reasonsFor(iterable $emails): \Illuminate\Support\Collection
    {
        $normalized = collect($emails)->filter()->map(fn ($e) => mb_strtolower(trim($e)))->unique()->values();

        if ($normalized->isEmpty()) {
            return collect();
        }

        return static::whereIn('email', $normalized)->pluck('reason', 'email');
    }
}
