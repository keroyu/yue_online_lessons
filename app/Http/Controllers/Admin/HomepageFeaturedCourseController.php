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
