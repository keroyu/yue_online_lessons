<?php

namespace App\Services;

class CloudflareStreamService
{
    /**
     * Build the playback iframe URL for a Stream video UID.
     *
     * With signing keys configured, the URL carries a short-lived RS256 JWT
     * instead of the raw UID (for videos with requireSignedURLs enabled).
     * Without keys (local dev), falls back to the unsigned UID URL.
     */
    public function signedEmbedUrl(string $uid): string
    {
        $token = $this->signToken($uid) ?? $uid;

        return "{$this->embedBaseUrl()}/{$token}/iframe";
    }

    /**
     * Unsigned iframe URL (used by VideoEmbedService::parse for a consistent
     * return shape; won't play videos that require signed URLs).
     */
    public function unsignedEmbedUrl(string $uid): string
    {
        return "{$this->embedBaseUrl()}/{$uid}/iframe";
    }

    /**
     * Create a signed playback JWT for the given video UID, or null when
     * signing keys are not configured.
     */
    private function signToken(string $uid): ?string
    {
        $keyId = config('services.cloudflare_stream.key_id');
        $pemBase64 = config('services.cloudflare_stream.private_key');

        if (!$keyId || !$pemBase64) {
            return null;
        }

        $privateKey = openssl_pkey_get_private(base64_decode($pemBase64));
        if ($privateKey === false) {
            return null;
        }

        $header = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'kid' => $keyId]));
        $payload = $this->base64UrlEncode(json_encode([
            'sub' => $uid,
            'kid' => $keyId,
            'exp' => time() + (int) config('services.cloudflare_stream.token_ttl', 43200),
        ]));

        openssl_sign("{$header}.{$payload}", $signature, $privateKey, OPENSSL_ALGO_SHA256);

        return "{$header}.{$payload}." . $this->base64UrlEncode($signature);
    }

    private function embedBaseUrl(): string
    {
        $customerCode = config('services.cloudflare_stream.customer_code');

        return $customerCode
            ? "https://customer-{$customerCode}.cloudflarestream.com"
            : 'https://iframe.videodelivery.net';
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
