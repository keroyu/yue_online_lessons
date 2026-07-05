<?php

namespace App\Http\Controllers;

use App\Http\Requests\RedeemCourseRequest;
use App\Models\Course;
use App\Services\RedemptionService;
use Illuminate\Http\RedirectResponse;

class RedemptionController extends Controller
{
    public function store(RedeemCourseRequest $request, Course $course, RedemptionService $service): RedirectResponse
    {
        $result = $service->redeem($request->user(), $course);

        if (! $result['success']) {
            return back()->withErrors(['redeem' => $result['error']]);
        }

        return redirect()->route('member.learning')
            ->with('success', '已使用積分兌換課程');
    }
}
