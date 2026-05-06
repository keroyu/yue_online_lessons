<?php

namespace App\Http\Middleware;

use App\Services\CartService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'email' => $request->user()->email,
                    'nickname' => $request->user()->nickname,
                    'real_name' => $request->user()->real_name,
                    'phone' => $request->user()->phone,
                    'role' => $request->user()->role,
                ] : null,
            ],
            'flash' => [
                'success'        => fn () => $request->session()->get('success'),
                'error'          => fn () => $request->session()->get('error'),
                'payment_failed' => fn () => $request->session()->get('payment_failed'),
                'drip_email'      => fn () => $request->session()->get('drip_email'),
                'drip_course_id'  => fn () => $request->session()->get('drip_course_id'),
                'drip_subscribed' => fn () => $request->session()->get('drip_subscribed'),
                'drip_nickname'   => fn () => $request->session()->get('drip_nickname'),
            ],
            'cartCount' => fn () => $request->user()
                ? app(CartService::class)->count($request->user()->id)
                : 0,
        ];
    }
}
