<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SuccessController extends Controller
{
    public function show(Request $request): Response
    {
        // TODO Phase 5: load order by merchant_order_no query param
        return Inertia::render('Payment/Success', [
            'order'       => null,
            'isLoggedIn'  => auth()->check(),
        ]);
    }
}
