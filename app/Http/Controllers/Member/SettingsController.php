<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Get user's order history
        $orders = $user->purchases()
            ->with('course:id,name')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($purchase) {
                return [
                    'id' => $purchase->id,
                    'course_name' => $purchase->course->name,
                    'amount' => $purchase->amount,
                    'currency' => $purchase->currency,
                    'status' => $purchase->status,
                    'type' => $purchase->type,
                    'type_label' => $purchase->type_label,
                    'created_at' => $purchase->created_at->toIso8601String(),
                ];
            });

        return Inertia::render('Member/Settings', [
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'nickname' => $user->nickname,
                'real_name' => $user->real_name,
                'phone' => $user->phone,
                'birth_date' => $user->birth_date?->format('Y-m-d'),
            ],
            'orders' => $orders,
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->update($request->validated());

        return back()->with('success', '資料已更新');
    }
}
