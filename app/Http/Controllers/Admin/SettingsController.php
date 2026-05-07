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
                'merchant_id' => SiteSetting::get('payuni_merchant_id', config('services.payuni.merchant_id', '')),
                'hash_key'    => '',
                'hash_iv'     => '',
            ],
            'newebpay' => [
                'merchant_id' => SiteSetting::get('newebpay_merchant_id', config('services.newebpay.merchant_id', '')),
                'hash_key'    => '',
                'hash_iv'     => '',
                'env'         => SiteSetting::get('newebpay_env', config('services.newebpay.env', 'sandbox')),
            ],
            'meta_pixel_id' => SiteSetting::get('meta_pixel_id', ''),
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
            'meta_pixel_id'        => ['nullable', 'string', 'max:30', 'regex:/^[0-9]*$/'],
        ]);

        $plainFields = ['payuni_merchant_id', 'newebpay_merchant_id', 'newebpay_env'];
        foreach ($plainFields as $key) {
            if ($request->has($key)) {
                SiteSetting::set($key, $request->input($key, ''));
            }
        }

        $secretFields = ['payuni_hash_key', 'payuni_hash_iv', 'newebpay_hash_key', 'newebpay_hash_iv'];
        foreach ($secretFields as $key) {
            $value = $request->input($key, '');
            if ($value !== '') {
                SiteSetting::set($key, $value);
            }
        }

        SiteSetting::set('meta_pixel_id', $request->input('meta_pixel_id', ''));

        return redirect()->back()->with('success', '金流設定已儲存');
    }
}
