<?php

namespace App\Services;

use App\Models\AiPrompt;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * The single OpenAI call site for the whole application (000 US10 / FR-029).
 *
 * Optional by design, same stance as ZoomMeetingService (011 D40): with no key
 * configured every AI path is skipped and the surrounding feature behaves as it
 * did before, so local development and CI never need a real key.
 */
class OpenAiService
{
    public const API_KEY = 'openai_api_key';
    public const DEFAULT_MODEL_KEY = 'openai_default_model';

    public function isEnabled(): bool
    {
        return $this->apiKey() !== '';
    }

    /**
     * Run one prompt against one input.
     *
     * Returns null — rather than throwing — when the feature is simply not
     * configured (no key, or the prompt row was never installed). Callers treat
     * that as "skip this step"; a genuine API failure still throws so the queue
     * can retry it.
     *
     * @throws \RuntimeException when OpenAI rejects the request
     */
    public function respond(string $promptKey, string $input): ?string
    {
        if (!$this->isEnabled()) {
            return null;
        }

        $prompt = AiPrompt::for($promptKey);

        if ($prompt === null) {
            Log::warning('OpenAI: prompt row is missing, skipping', ['key' => $promptKey]);

            return null;
        }

        $model = $prompt->resolvedModel();

        if ($model === '') {
            Log::warning('OpenAI: no model resolved, skipping', ['key' => $promptKey]);

            return null;
        }

        $response = Http::withToken($this->apiKey())
            ->acceptJson()
            ->timeout((int) config('ai.timeout', 120))
            ->retry(
                (int) config('ai.retry_times', 2),
                (int) config('ai.retry_sleep_ms', 2000),
                throw: false
            )
            ->post((string) config('ai.endpoint'), [
                'model'             => $model,
                'instructions'      => $prompt->instructions,
                'input'             => $input,
                'max_output_tokens' => $prompt->resolvedMaxOutputTokens(),
            ]);

        if (!$response->successful()) {
            // The body is the whole diagnosis and dropping it is how a missing
            // scope, a disabled key, and an unknown model all end up looking
            // like the same bare status code.
            throw new \RuntimeException(
                'OpenAI request failed: ' . $response->status() . ' ' . $response->body()
            );
        }

        return $this->extractText($response->json());
    }

    /**
     * `output_text` is the convenience field; the nested path is the contract.
     * Read both so a response shape change degrades to null rather than a
     * fatal, and let the caller decide what an empty result means.
     */
    private function extractText(mixed $body): ?string
    {
        if (!is_array($body)) {
            return null;
        }

        $text = $body['output_text'] ?? null;

        if (!is_string($text) || trim($text) === '') {
            $text = data_get($body, 'output.0.content.0.text');
        }

        return is_string($text) && trim($text) !== '' ? trim($text) : null;
    }

    private function apiKey(): string
    {
        return trim((string) SiteSetting::get(self::API_KEY, ''));
    }
}
