<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    public function show(): Response
    {
        // TODO Phase 4: load cart items, prefill from auth user
        return Inertia::render('Checkout/Index', [
            'items'   => [],
            'total'   => 0,
            'prefill' => [
                'name'  => auth()->user()?->real_name,
                'email' => auth()->user()?->email,
                'phone' => auth()->user()?->phone,
            ],
        ]);
    }

    public function initiate(Request $request)
    {
        // TODO Phase 4
        return response()->json(['message' => 'Not implemented yet'], 501);
    }
}
