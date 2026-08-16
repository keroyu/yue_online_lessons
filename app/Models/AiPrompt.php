<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One row per AI call site (000 US10).
 *
 * `key`, `feature`, and `label` are owned by the code and deliberately absent
 * from `$fillable` — the admin panel may only tune how a prompt behaves, never
 * which prompts exist (FR-027).
 */
class AiPrompt extends Model
{
    protected $fillable = [
        'instructions',
        'model',
        'max_output_tokens',
    ];

    protected function casts(): array
    {
        return [
            'max_output_tokens' => 'integer',
            'sort_order'        => 'integer',
        ];
    }

    public static function for(string $key): ?self
    {
        return static::where('key', $key)->first();
    }

    /**
     * The model this prompt should run on.
     *
     * Resolution order is per-prompt → site-wide → config (FR-029), so most
     * prompts can leave `model` null and follow whatever the site is set to.
     */
    public function resolvedModel(): string
    {
        $candidates = [
            $this->model,
            SiteSetting::get('openai_default_model', ''),
            config('ai.default_model'),
        ];

        foreach ($candidates as $candidate) {
            $candidate = trim((string) $candidate);
            if ($candidate !== '') {
                return $candidate;
            }
        }

        return '';
    }

    public function resolvedMaxOutputTokens(): int
    {
        return $this->max_output_tokens ?: (int) config('ai.max_output_tokens', 4000);
    }
}
