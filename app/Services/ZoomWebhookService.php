<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Http\Request;

/**
 * Zoom webhook authentication (011 US23 / FR-101–FR-102).
 *
 * Shaped after PortalyWebhookService: verification and secret lookup live here
 * so the controller is only routing.
 */
class ZoomWebhookService
{
    public const SECRET_KEY = 'zoom_webhook_secret_token';

    /** Zoom's own replay window. Anything older is a resend or an attack. */
    private const MAX_AGE_SECONDS = 300;

    public function isEnabled(): bool
    {
        return $this->secret() !== '';
    }

    /**
     * Answer Zoom's CRC handshake.
     *
     * Deliberately not signature-checked by the caller: this exchange is what
     * establishes the trust that signatures later rely on, so demanding a valid
     * signature first would be asking Zoom to prove something not yet agreed.
     *
     * @return array{plainToken: string, encryptedToken: string}
     */
    public function challengeResponse(string $plainToken): array
    {
        return [
            'plainToken'     => $plainToken,
            'encryptedToken' => hash_hmac('sha256', $plainToken, $this->secret()),
        ];
    }

    public function verify(Request $request): bool
    {
        $secret = $this->secret();

        // No secret means no way to tell Zoom from anyone else. Refusing beats
        // degrading to an open endpoint (000 D27 learned this the hard way).
        if ($secret === '') {
            return false;
        }

        $timestamp = (string) $request->header('x-zm-request-timestamp', '');
        $signature = (string) $request->header('x-zm-signature', '');

        if ($timestamp === '' || $signature === '') {
            return false;
        }

        if (abs(time() - (int) $timestamp) > self::MAX_AGE_SECONDS) {
            return false;
        }

        // The raw body, never a re-encoded array — any reordering or whitespace
        // difference changes the HMAC.
        $message = 'v0:' . $timestamp . ':' . $request->getContent();

        return hash_equals('v0=' . hash_hmac('sha256', $message, $secret), $signature);
    }

    private function secret(): string
    {
        return trim((string) SiteSetting::get(self::SECRET_KEY, ''));
    }
}
