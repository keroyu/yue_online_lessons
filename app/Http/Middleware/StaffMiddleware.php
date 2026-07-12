<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StaffMiddleware
{
    /**
     * Admits admins and sales consultants (canAccessSalesPanel). Guards the
     * staff subset of /admin (coupons, coupon chains, high-ticket leads).
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check() || !auth()->user()->canAccessSalesPanel()) {
            return redirect('/')
                ->with('error', '您沒有權限存取此頁面');
        }

        return $next($request);
    }
}
