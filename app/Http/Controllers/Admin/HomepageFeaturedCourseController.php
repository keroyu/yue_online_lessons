<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreFeaturedCourseRequest;
use App\Http\Requests\Admin\UpdateFeaturedCourseRequest;
use App\Models\HomepageFeaturedCourse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HomepageFeaturedCourseController extends Controller
{
    public function store(StoreFeaturedCourseRequest $request): RedirectResponse
    {
        HomepageFeaturedCourse::create([
            'course_id'  => $request->input('course_id'),
            'blurb'      => $request->input('blurb'),
            'sort_order' => (HomepageFeaturedCourse::max('sort_order') ?? 0) + 1,
        ]);

        return redirect()->back()->with('success', '精選課程已新增');
    }

    public function update(UpdateFeaturedCourseRequest $request, HomepageFeaturedCourse $featuredCourse): RedirectResponse
    {
        $featuredCourse->update(['blurb' => $request->input('blurb')]);

        return redirect()->back()->with('success', '介紹文字已更新');
    }

    /**
     * Show or hide one featured course (002 US19 / FR-051).
     *
     * Deliberately not folded into `update()`: that one writes `blurb`, and the
     * blurb in the admin panel is a draft until 儲存介紹 is pressed — sharing an
     * endpoint would let a visibility toggle decide the fate of a half-written
     * sentence (D53).
     *
     * Takes an explicit value rather than flipping the stored one, so a double
     * click or a second tab cannot settle on a state nobody asked for. Inline
     * validation matches `reorder()` below; a single boolean does not need a
     * third Form Request class.
     */
    public function toggleVisibility(Request $request, HomepageFeaturedCourse $featuredCourse): RedirectResponse
    {
        $validated = $request->validate([
            'is_visible' => ['required', 'boolean'],
        ], [
            'is_visible.required' => '請指定顯示或隱藏',
        ]);

        $featuredCourse->update(['is_visible' => $validated['is_visible']]);

        return redirect()->back()->with(
            'success',
            $validated['is_visible'] ? '已顯示於首頁右欄' : '已從首頁右欄隱藏'
        );
    }

    public function destroy(HomepageFeaturedCourse $featuredCourse): RedirectResponse
    {
        $featuredCourse->delete();

        return redirect()->back()->with('success', '精選課程已移除');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids'   => ['required', 'array'],
            'ids.*' => ['integer', 'exists:homepage_featured_courses,id'],
        ]);

        foreach ($validated['ids'] as $index => $id) {
            HomepageFeaturedCourse::where('id', $id)->update(['sort_order' => $index + 1]);
        }

        return redirect()->back()->with('success', '排序已更新');
    }
}
