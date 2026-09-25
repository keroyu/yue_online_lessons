<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreHomepageWidgetRequest;
use App\Http\Requests\Admin\UpdateHomepageWidgetRequest;
use App\Models\HomepageWidget;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Homepage blocks: order, visibility and the admin's own HTML ones (002 US23).
 *
 * Its own controller rather than a sixth concern in `HomepageSettingController`
 * (002 FR-083), following the shape `HomepageFeaturedCourseController` already
 * set: CRUD plus a `reorder` and a separate `toggleVisibility`.
 */
class HomepageWidgetController extends Controller
{
    public function store(StoreHomepageWidgetRequest $request): RedirectResponse
    {
        $area = $request->input('area');

        HomepageWidget::create([
            'key'        => null,
            'type'       => HomepageWidget::TYPE_HTML,
            'area'       => $area,
            'title'      => $request->input('title'),
            'html'       => $request->input('html'),
            'sort_order' => ((int) HomepageWidget::area($area)->max('sort_order')) + 1,
            'is_visible' => true,
        ]);

        return redirect()->back()->with('success', '自訂區塊已新增');
    }

    public function update(UpdateHomepageWidgetRequest $request, HomepageWidget $widget): RedirectResponse
    {
        $this->rejectBuiltin($widget);

        $widget->update([
            'title' => $request->input('title'),
            'html'  => $request->input('html'),
            'area'  => $request->input('area'),
        ]);

        return redirect()->back()->with('success', '自訂區塊已更新');
    }

    public function destroy(HomepageWidget $widget): RedirectResponse
    {
        $this->rejectBuiltin($widget);

        $widget->delete();

        return redirect()->back()->with('success', '自訂區塊已刪除');
    }

    /**
     * Show or hide one block (002 FR-074).
     *
     * Available to built-ins too — that is the whole point of the switch — but
     * separate from `update()`, which writes an HTML draft that only lands when
     * 儲存 is pressed (same reasoning as FR-051). Sets the value it is given
     * rather than flipping the stored one, so a double click or a second tab
     * cannot settle on a state nobody asked for.
     */
    public function toggleVisibility(Request $request, HomepageWidget $widget): RedirectResponse
    {
        $validated = $request->validate([
            'is_visible' => ['required', 'boolean'],
        ], [
            'is_visible.required' => '請指定顯示或隱藏',
        ]);

        $widget->update(['is_visible' => $validated['is_visible']]);

        return redirect()->back()->with(
            'success',
            $validated['is_visible'] ? '區塊已顯示' : '區塊已隱藏'
        );
    }

    /**
     * Reorder one column.
     *
     * The posted list is normalised against the column rather than trusted
     * (002 FR-081): ids from the other column are dropped, and widgets the
     * sender never knew about — created in another tab between its last load
     * and this submit — keep their relative position at the end instead of
     * being silently reshuffled.
     */
    public function reorder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'area'  => ['required', Rule::in(HomepageWidget::AREAS)],
            'ids'   => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $current = HomepageWidget::area($validated['area'])->ordered()->pluck('id')->all();

        $ordered = array_values(array_intersect($validated['ids'], $current));
        $ordered = array_merge($ordered, array_values(array_diff($current, $ordered)));

        foreach ($ordered as $index => $id) {
            HomepageWidget::where('id', $id)->update(['sort_order' => $index]);
        }

        return redirect()->back()->with('success', '區塊排序已更新');
    }

    /**
     * Built-ins expose only `is_visible` and `sort_order`.
     *
     * Enforced here and not merely hidden in the UI: deleting `course_catalog`
     * would leave the homepage unable to list a single course, and there is no
     * screen anywhere that puts it back.
     */
    private function rejectBuiltin(HomepageWidget $widget): void
    {
        abort_if($widget->isBuiltin(), 403, '預設區塊不可編輯或刪除');
    }
}
