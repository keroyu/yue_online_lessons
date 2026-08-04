<?php

namespace App\Services;

use App\Models\SiteSetting;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Zoom Server-to-Server OAuth (011 US12).
 *
 * Optional by design (FR-039 / D40): with no credentials configured the whole
 * path is skipped and booking behaves exactly as it did before this story, so
 * local development and CI never need a real Zoom account.
 */
class ZoomMeetingService
{
    public const ACCOUNT_ID_KEY = 'zoom_account_id';
    public const CLIENT_ID_KEY = 'zoom_client_id';
    public const CLIENT_SECRET_KEY = 'zoom_client_secret';

    private const TOKEN_URL = 'https://zoom.us/oauth/token';
    private const API_BASE = 'https://api.zoom.us/v2';

    /** Zoom tokens last an hour; refresh a little early rather than mid-call. */
    private const TOKEN_TTL_MINUTES = 55;

    /**
     * These calls now run inside the visitor's confirmation request (D55), so
     * they need a ceiling: Laravel's default is 30s per call, and a token
     * refresh plus a create would let one Zoom outage hold the page for a
     * minute. Two quick tries absorb a dropped connection without adding
     * anything measurable to the happy path.
     */
    private const TIMEOUT_SECONDS = 8;
    private const RETRY_TIMES = 2;
    private const RETRY_SLEEP_MS = 300;

    public function isEnabled(): bool
    {
        return $this->accountId() !== '' && $this->clientId() !== '' && $this->clientSecret() !== '';
    }

    /**
     * Create a scheduled meeting for a confirmed booking.
     *
     * @return array{meeting_id: string, join_url: string}
     * @throws \RuntimeException when Zoom rejects the request
     */
    public function createMeeting(CarbonInterface $startsAt, int $minutes, string $topic): array
    {
        $response = $this->client()
            ->post(self::API_BASE . '/users/me/meetings', [
                'topic'      => $topic,
                'type'       => 2, // scheduled
                'start_time' => Carbon::instance($startsAt)->utc()->format('Y-m-d\TH:i:s\Z'),
                'duration'   => $minutes,
                'timezone'   => ConsultationSlotService::DISPLAY_TZ,
                'settings'   => [
                    'join_before_host' => true,
                    'waiting_room'     => false,
                ],
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Zoom meeting create failed: ' . $response->status() . ' ' . $response->body());
        }

        $body = $response->json();

        return [
            'meeting_id' => (string) ($body['id'] ?? ''),
            'join_url'   => (string) ($body['join_url'] ?? ''),
        ];
    }

    /**
     * Move an existing meeting (011 US14 / FR-050 / D52).
     *
     * PATCH rather than delete-and-recreate on purpose: the `join_url` survives,
     * so the link already sitting in the applicant's calendar entry — and in the
     * confirmation mail they may have starred — keeps working.
     *
     * @throws \RuntimeException when Zoom rejects the request
     */
    public function updateMeeting(string $meetingId, CarbonInterface $startsAt, int $minutes): void
    {
        $response = $this->client()
            ->patch(self::API_BASE . '/meetings/' . $meetingId, [
                'start_time' => Carbon::instance($startsAt)->utc()->format('Y-m-d\TH:i:s\Z'),
                'duration'   => $minutes,
                'timezone'   => ConsultationSlotService::DISPLAY_TZ,
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Zoom meeting update failed: ' . $response->status() . ' ' . $response->body());
        }
    }

    /**
     * Withdraw a meeting. A 404 counts as success — this call is a declaration
     * that the meeting should not exist, and somebody having already removed it
     * by hand is that declaration being true, not an error worth retrying.
     *
     * @throws \RuntimeException when Zoom rejects the request for any other reason
     */
    public function deleteMeeting(string $meetingId): void
    {
        $response = $this->client()->delete(self::API_BASE . '/meetings/' . $meetingId);

        if ($response->successful() || $response->status() === 404) {
            return;
        }

        throw new \RuntimeException('Zoom meeting delete failed: ' . $response->status() . ' ' . $response->body());
    }

    /**
     * Account-credentials grant. Cached per client id so rotating the
     * credentials in the admin panel does not keep serving the old token.
     */
    public function token(): string
    {
        $clientId = $this->clientId();

        return Cache::remember(
            'zoom_access_token_' . md5($clientId),
            now()->addMinutes(self::TOKEN_TTL_MINUTES),
            function () use ($clientId) {
                $response = Http::asForm()
                    ->timeout(self::TIMEOUT_SECONDS)
                    ->retry(self::RETRY_TIMES, self::RETRY_SLEEP_MS, throw: false)
                    ->withBasicAuth($clientId, $this->clientSecret())
                    ->post(self::TOKEN_URL, [
                        'grant_type' => 'account_credentials',
                        'account_id' => $this->accountId(),
                    ]);

                if (!$response->successful()) {
                    throw new \RuntimeException('Zoom token request failed: ' . $response->status());
                }

                return (string) $response->json('access_token');
            }
        );
    }

    /** Authenticated, bounded, and retried once — see the constants above. */
    private function client(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withToken($this->token())
            ->acceptJson()
            ->timeout(self::TIMEOUT_SECONDS)
            ->retry(self::RETRY_TIMES, self::RETRY_SLEEP_MS, throw: false);
    }

    private function accountId(): string
    {
        return trim((string) SiteSetting::get(self::ACCOUNT_ID_KEY, ''));
    }

    private function clientId(): string
    {
        return trim((string) SiteSetting::get(self::CLIENT_ID_KEY, ''));
    }

    private function clientSecret(): string
    {
        return trim((string) SiteSetting::get(self::CLIENT_SECRET_KEY, ''));
    }
}
