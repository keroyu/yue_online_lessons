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

        $text = $this->extractText($response->json());

        if ($text === null) {
            // A 200 with nothing we can read is a parsing bug or a response
            // shape change, not a configuration choice — and it must never be
            // as quiet as the "not configured" null above. That silence is
            // exactly how the first summaries came back empty with a completed
            // request and 732 billed output tokens sitting in the response.
            Log::warning('OpenAI: request succeeded but no text could be extracted', [
                'key'    => $promptKey,
                'model'  => $model,
                'status' => data_get($response->json(), 'status'),
                'types'  => array_map(
                    fn ($item) => is_array($item) ? ($item['type'] ?? '?') : '?',
                    (array) data_get($response->json(), 'output', [])
                ),
            ]);
        }

        return $text;
    }

    /**
     * `output_text` is the convenience field; walking `output` is the contract.
     *
     * The walk is not optional and the index is not zero: a reasoning model puts
     * a `reasoning` item first and the `message` after it, so the obvious
     * `output.0.content.0.text` reads an empty reasoning block and concludes the
     * model said nothing. Find the message items by type and take every
     * text-bearing part of them.
     */
    private function extractText(mixed $body): ?string
    {
        if (!is_array($body)) {
            return null;
        }

        $text = $body['output_text'] ?? null;

        if (is_string($text) && trim($text) !== '') {
            return trim($text);
        }

        $parts = [];

        foreach ((array) ($body['output'] ?? []) as $item) {
            if (!is_array($item) || ($item['type'] ?? null) !== 'message') {
                continue;
            }

            foreach ((array) ($item['content'] ?? []) as $content) {
                $part = is_array($content) ? ($content['text'] ?? null) : null;

                if (is_string($part) && trim($part) !== '') {
                    $parts[] = $part;
                }
            }
        }

        $joined = trim(implode("\n", $parts));

        return $joined !== '' ? $joined : null;
    }

    private function apiKey(): string
    {
        return trim((string) SiteSetting::get(self::API_KEY, ''));
    }
}
