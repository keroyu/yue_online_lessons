<?php

namespace App\Services;

use App\Jobs\SendMetaConversionJob;
use App\Models\SiteSetting;
use Illuminate\Http\Request;

class MetaConversionsService
{
    /**
     * Queue a Conversions API event. Silently no-ops when the pixel id or
     * access token is not configured (FR-011: tracking must never block business flow).
     *
     * @param array<string, mixed> $userData   pre-hashed user_data fields (em/ph/external_id/client_ip_address/...)
     * @param array<string, mixed> $customData value/currency/content_ids/...
     */
    public function send(
        string $eventName,
        array $userData,
        array $customData = [],
        ?string $eventId = null,
        ?string $sourceUrl = null,
    ): void {
        $pixelId = SiteSetting::get('meta_pixel_id', config('services.meta.pixel_id', ''));
        $accessToken = SiteSetting::get('meta_capi_access_token', '');

        if (!$pixelId || !$accessToken) {
            return;
        }

        SendMetaConversionJob::dispatch([
            'pixel_id'         => $pixelId,
            'event_name'       => $eventName,
            'event_id'         => $eventId,
            'event_time'       => now()->timestamp,
            'event_source_url' => $sourceUrl,
            'user_data'        => array_filter($userData, fn ($v) => $v !== null && $v !== ''),
            'custom_data'      => $customData,
        ]);
    }

    /**
     * Build user_data from request context (ip / user agent / fbp / fbc cookies).
     *
     * @return array<string, mixed>
     */
    public function userDataFromRequest(?Request $request): array
    {
        if (!$request) {
            return [];
        }

        return [
            'client_ip_address' => $request->ip(),
            'client_user_agent' => $request->userAgent(),
            'fbp'               => $request->cookie('_fbp'),
            'fbc'               => $request->cookie('_fbc'),
        ];
    }

    /** SHA-256 of lowercased, trimmed email (Meta normalization rules). */
    public function hashEmail(?string $email): ?string
    {
        $email = strtolower(trim((string) $email));

        return $email === '' ? null : hash('sha256', $email);
    }

    /** SHA-256 of digits-only phone with Taiwan country code (leading 0 → 886). */
    public function hashPhone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        if ($digits === '') {
            return null;
        }
        if (str_starts_with($digits, '0')) {
            $digits = '886' . substr($digits, 1);
        }

        return hash('sha256', $digits);
    }
}
