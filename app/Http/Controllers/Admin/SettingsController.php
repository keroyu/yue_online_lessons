<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
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
            'meta_pixel_id' => SiteSetting::get('meta_pixel_id', ''),
            'meta_capi' => [
                'access_token'         => '',
                'access_token_preview' => $this->maskSecret(SiteSetting::get('meta_capi_access_token', '')),
                'test_event_code'      => SiteSetting::get('meta_capi_test_event_code', ''),
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
            'meta_pixel_id'        => ['nullable', 'string', 'max:30', 'regex:/^[0-9]*$/'],
            'meta_capi_access_token'    => ['nullable', 'string', 'max:500'],
            'meta_capi_test_event_code' => ['nullable', 'string', 'max:50'],
        ]);

        $plainFields = ['payuni_merchant_id', 'newebpay_merchant_id', 'newebpay_env'];
        foreach ($plainFields as $key) {
            if ($request->has($key)) {
                SiteSetting::set($key, $request->input($key, ''));
            }
        }

        $secretFields = ['payuni_hash_key', 'payuni_hash_iv', 'newebpay_hash_key', 'newebpay_hash_iv', 'portaly_webhook_key', 'meta_capi_access_token'];
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
