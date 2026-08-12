<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCoursePlanRequest;
use App\Http\Requests\Admin\SyncLessonPlansRequest;
use App\Http\Requests\Admin\SyncPlanLessonsRequest;
use App\Models\Course;
use App\Models\CoursePlan;
use App\Models\Lesson;
use Illuminate\Http\RedirectResponse;

/**
 * Course plans (011 US21): named tiers of a high-ticket course, each covering
 * a subset of its lessons. Plans may overlap freely.
 */
class CoursePlanController extends Controller
{
    /**
     * Plans are high-ticket only (D82). Normal courses sell through the
     * storefront, which has no plan picker — a plan there would be settable in
     * the admin and unbuyable on the front end.
     */
    public function store(StoreCoursePlanRequest $request, Course $course): RedirectResponse
    {
        if (!$course->is_high_ticket) {
            abort(403, '只有高價課可以設定多方案');
        }

        $course->plans()->create([
            'name' => $request->validated('name'),
            'price' => $request->validated('price'),
            'sort_order' => ($course->plans()->max('sort_order') ?? -1) + 1,
        ]);

        return back()->with('success', '方案已新增');
    }

    public function update(StoreCoursePlanRequest $request, CoursePlan $plan): RedirectResponse
    {
        $plan->update([
            'name' => $request->validated('name'),
            'price' => $request->validated('price'),
        ]);

        return back()->with('success', '方案已更新');
    }

    /**
     * A plan somebody still holds must survive (FR-093). The foreign key is
     * `restrict` for the same reason — nulling those purchases would silently
     * promote their owners to full access, which is the failure mode nobody
     * would ever notice.
     */
    public function destroy(CoursePlan $plan): RedirectResponse
    {
        $holders = $plan->purchases()->count();

        if ($holders > 0) {
            return back()->withErrors([
                'plan' => "還有 {$holders} 位會員持有此方案，請先到會員管理改成其他方案再刪除",
            ]);
        }

        $plan->lessons()->detach();
        $plan->delete();

        return back()->with('success', '方案已刪除');
    }

    /** Replace this lesson's plan assignments wholesale. */
    public function syncLessons(SyncLessonPlansRequest $request, Lesson $lesson): RedirectResponse
    {
        $lesson->plans()->sync($request->validated('plan_ids'));

        return back();
    }

    /**
     * Replace this plan's lesson set wholesale — the plan-side counterpart of
     * syncLessons(), used by the per-chapter shortcuts on the plan card.
     *
     * The caller sends the complete resulting set rather than a delta: the
     * browser already knows the current membership, and a full set means a
     * double-click cannot drift the two sides apart.
     */
    public function syncPlanLessons(SyncPlanLessonsRequest $request, CoursePlan $plan): RedirectResponse
    {
        $plan->lessons()->sync($request->validated('lesson_ids'));

        return back();
    }
}
