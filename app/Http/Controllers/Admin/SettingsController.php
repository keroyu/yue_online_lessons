<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiPrompt;
use App\Models\SiteSetting;
use App\Services\OpenAiService;
use App\Services\ZoomMeetingService;
use App\Services\ZoomWebhookService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function showPayment(): Response
    {
        return Inertia::render('Admin/Settings/Payment', [
            'payuni' => [
                'merchant_id'      => SiteSetting::get('payuni_merchant_id', config('services.payuni.merchant_id', '')),
                'hash_key'         => '',
                'hash_iv'          => '',
                'hash_key_preview' => $this->maskSecret(SiteSetting::get('payuni_hash_key', config('services.payuni.hash_key', ''))),
                'hash_iv_preview'  => $this->maskSecret(SiteSetting::get('payuni_hash_iv', config('services.payuni.hash_iv', ''))),
            ],
            'newebpay' => [
                'merchant_id'      => SiteSetting::get('newebpay_merchant_id', config('services.newebpay.merchant_id', '')),
                'hash_key'         => '',
                'hash_iv'          => '',
                'hash_key_preview' => $this->maskSecret(SiteSetting::get('newebpay_hash_key', config('services.newebpay.hash_key', ''))),
                'hash_iv_preview'  => $this->maskSecret(SiteSetting::get('newebpay_hash_iv', config('services.newebpay.hash_iv', ''))),
                'env'              => SiteSetting::get('newebpay_env', config('services.newebpay.env', 'sandbox')),
            ],
            'portaly' => [
                'webhook_key'         => '',
                'webhook_key_preview' => $this->maskSecret(SiteSetting::get('portaly_webhook_key', '')),
            ],
            'resend' => [
                'webhook_secret'         => '',
                'webhook_secret_preview' => $this->maskSecret(SiteSetting::get('resend_webhook_secret', '')),
            ],
            'meta_pixel_id' => SiteSetting::get('meta_pixel_id', ''),
            'meta_capi' => [
                'access_token'         => '',
                'access_token_preview' => $this->maskSecret(SiteSetting::get('meta_capi_access_token', '')),
                'test_event_code'      => SiteSetting::get('meta_capi_test_event_code', ''),
            ],
            // Zoom Server-to-Server OAuth (011 US12 / D41). account_id and
            // client_id are identifiers, not secrets — masking them would only
            // make them harder to check against the Zoom app page.
            'zoom' => [
                'account_id'            => SiteSetting::get(ZoomMeetingService::ACCOUNT_ID_KEY, ''),
                'client_id'             => SiteSetting::get(ZoomMeetingService::CLIENT_ID_KEY, ''),
                'client_secret'         => '',
                'client_secret_preview' => $this->maskSecret(SiteSetting::get(ZoomMeetingService::CLIENT_SECRET_KEY, '')),
                'enabled'               => app(ZoomMeetingService::class)->isEnabled(),
                // 011 US23: the recording webhook's Secret Token. Blank means
                // the endpoint refuses every event rather than running unsigned.
                'webhook_secret'         => '',
                'webhook_secret_preview' => $this->maskSecret(SiteSetting::get(ZoomWebhookService::SECRET_KEY, '')),
            ],
        ]);
    }

    public function updatePayment(Request $request): RedirectResponse
    {
        $request->validate([
            'payuni_merchant_id'   => ['nullable', 'string', 'max:50'],
            'payuni_hash_key'      => ['nullable', 'string', 'max:200'],
            'payuni_hash_iv'       => ['nullable', 'string', 'max:200'],
            'newebpay_merchant_id' => ['nullable', 'string', 'max:50'],
            'newebpay_hash_key'    => ['nullable', 'string', 'max:200'],
            'newebpay_hash_iv'     => ['nullable', 'string', 'max:200'],
            'newebpay_env'         => ['nullable', 'string', 'in:sandbox,production'],
            'portaly_webhook_key'  => ['nullable', 'string', 'max:200'],
            'resend_webhook_secret' => ['nullable', 'string', 'max:200'],
            'meta_pixel_id'        => ['nullable', 'string', 'max:30', 'regex:/^[0-9]*$/'],
            'meta_capi_access_token'    => ['nullable', 'string', 'max:500'],
            'meta_capi_test_event_code' => ['nullable', 'string', 'max:50'],
            'zoom_account_id'    => ['nullable', 'string', 'max:100'],
            'zoom_client_id'     => ['nullable', 'string', 'max:100'],
            'zoom_client_secret' => ['nullable', 'string', 'max:200'],
            'zoom_webhook_secret_token' => ['nullable', 'string', 'max:200'],
        ]);

        $plainFields = ['payuni_merchant_id', 'newebpay_merchant_id', 'newebpay_env', 'zoom_account_id', 'zoom_client_id'];
        foreach ($plainFields as $key) {
            if ($request->has($key)) {
                SiteSetting::set($key, $request->input($key, ''));
            }
        }

        $secretFields = ['payuni_hash_key', 'payuni_hash_iv', 'newebpay_hash_key', 'newebpay_hash_iv', 'portaly_webhook_key', 'meta_capi_access_token', 'resend_webhook_secret', 'zoom_client_secret', 'zoom_webhook_secret_token'];
        foreach ($secretFields as $key) {
            // ConvertEmptyStringsToNull turns '' into null; both mean "keep the old secret"
            $value = (string) $request->input($key, '');
            if ($value !== '') {
                SiteSetting::set($key, $value);
            }
        }

        SiteSetting::set('meta_pixel_id', $request->input('meta_pixel_id', ''));
        SiteSetting::set('meta_capi_test_event_code', $request->input('meta_capi_test_event_code') ?? '');

        return redirect()->back()->with('success', 'API 設定已儲存');
    }

    /**
     * Site-wide AI settings (000 US10).
     *
     * Separate page rather than another card on the payment screen: this one is
     * expected to keep growing a group per AI feature (D31), and every save here
     * changes behaviour and cost for the whole site — hence admin-only.
     */
    public function showAi(): Response
    {
        $features = (array) config('ai.features', []);

        return Inertia::render('Admin/Settings/Ai', [
            'credentials' => [
                'api_key'         => '',
                'api_key_preview' => $this->maskSecret(SiteSetting::get(OpenAiService::API_KEY, '')),
                'default_model'   => SiteSetting::get(OpenAiService::DEFAULT_MODEL_KEY, ''),
                'enabled'         => app(OpenAiService::class)->isEnabled(),
            ],
            'models'   => (array) config('ai.models', []),
            'features' => $features,
            // Grouped by feature so the page renders whatever exists; adding an
            // AI feature is a migration, not a Vue change (US10 acceptance).
            'prompts' => AiPrompt::query()
                ->orderBy('feature')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->map(fn (AiPrompt $prompt) => [
                    'id'                => $prompt->id,
                    'key'               => $prompt->key,
                    'feature'           => $prompt->feature,
                    'feature_label'     => $features[$prompt->feature] ?? $prompt->feature,
                    'label'             => $prompt->label,
                    'description'       => $prompt->description,
                    'instructions'      => $prompt->instructions,
                    'model'             => $prompt->model,
                    'max_output_tokens' => $prompt->max_output_tokens,
                    'updated_at'        => $prompt->updated_at?->toIso8601String(),
                ])
                ->values(),
        ]);
    }

    public function updateAi(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'openai_api_key'       => ['nullable', 'string', 'max:200'],
            'openai_default_model' => ['nullable', 'string', 'max:50'],
            'prompts'                      => ['sometimes', 'array'],
            'prompts.*.id'                 => ['required', 'integer'],
            'prompts.*.instructions'       => ['required', 'string', 'max:20000'],
            'prompts.*.model'              => ['nullable', 'string', 'max:50'],
            'prompts.*.max_output_tokens'  => ['nullable', 'integer', 'min:1', 'max:128000'],
        ]);

        // Blank means "keep the stored secret" — same contract as every other
        // credential field on the site (D3).
        $apiKey = (string) $request->input('openai_api_key', '');
        if ($apiKey !== '') {
            SiteSetting::set(OpenAiService::API_KEY, $apiKey);
        }

        SiteSetting::set(OpenAiService::DEFAULT_MODEL_KEY, $request->input('openai_default_model') ?? '');

        // Only ever updates rows that already exist, and only the three tunable
        // columns — `key` / `feature` / `label` belong to the code (FR-027).
        foreach ($validated['prompts'] ?? [] as $row) {
            $prompt = AiPrompt::find($row['id']);

            if ($prompt === null) {
                continue;
            }

            $model = trim((string) ($row['model'] ?? ''));

            $prompt->update([
                'instructions'      => $row['instructions'],
                'model'             => $model !== '' ? $model : null,
                'max_output_tokens' => $row['max_output_tokens'] ?? null,
            ]);
        }

        return redirect()->back()->with('success', 'AI 設定已儲存');
    }

    public function showPoints(Request $request): Response
    {
        // Referral performance shares this page (US5 merged into 積分與推薦).
        $range = (string) $request->input('range', '30');
        $days = $range === 'all' ? null : (int) $range;

        return Inertia::render('Admin/Settings/Points', [
            'points' => [
                'referral_threshold_amount' => (int) SiteSetting::get('referral_threshold_amount', 3000),
                'referral_reward_rate'      => (int) SiteSetting::get('referral_reward_rate', 10),
                'homework_reward_points'    => (int) SiteSetting::get('homework_reward_points', 100),
                'referral_maturity_days'    => (int) SiteSetting::get('referral_maturity_days', 14),
                'referral_discount_amount'  => (int) SiteSetting::get('referral_discount_amount', 150),
            ],
            'referral' => [
                'rows'  => app(\App\Services\ReferralService::class)->performanceRows($days),
                'range' => $range,
            ],
        ]);
    }

    public function updatePoints(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'referral_threshold_amount' => ['required', 'integer', 'min:0'],
            'referral_reward_rate'      => ['required', 'integer', 'min:0', 'max:100'],
            'homework_reward_points'    => ['required', 'integer', 'min:0'],
            'referral_maturity_days'    => ['required', 'integer', 'min:0'],
            'referral_discount_amount'  => ['required', 'integer', 'min:0'],
        ]);

        // New values apply only to points generated afterwards; existing ledger is snapshotted (FR-027).
        foreach ($validated as $key => $value) {
            SiteSetting::set($key, (string) $value);
        }

        return redirect()->back()->with('success', '積分設定已儲存');
    }

    private function maskSecret(?string $value): string
    {
        if (!$value) {
            return '';
        }
        return mb_substr($value, 0, 5) . str_repeat('*', max(0, mb_strlen($value) - 5));
    }
}
