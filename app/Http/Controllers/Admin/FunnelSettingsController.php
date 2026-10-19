<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateFunnelSettingsRequest;
use App\Models\User;
use App\Support\ScreeningRubric;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * 1v1 漏斗設定 (011 US46): the screening questions, the rules that turn people
 * away and the consultant routing, on one page, for the whole site.
 */
class FunnelSettingsController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('Admin/FunnelSettings/Edit', [
            'rubric'          => $this->withAnsweredFlags(ScreeningRubric::current()),
            'consultantCount' => User::where(fn ($q) => $q->where('role', 'admin')->orWhere('is_sales_consultant', true))->count(),
        ]);
    }

    public function update(UpdateFunnelSettingsRequest $request): RedirectResponse
    {
        ScreeningRubric::save($request->rubric());

        return redirect()->back()->with('success', '1v1 漏斗設定已儲存');
    }

    /**
     * Marks what somebody has already answered, so the page can say that
     * deleting it will retire it instead (FR-265 (6)).
     */
    private function withAnsweredFlags(array $rubric): array
    {
        $used = ScreeningRubric::answeredKeys();

        foreach ($rubric['questions'] as &$question) {
            $question['answered'] = isset($used['questions'][$question['key']]);

            foreach ($question['options'] as &$option) {
                $option['answered'] = isset($used['options']["{$question['key']}.{$option['key']}"]);
            }
            unset($option);
        }
        unset($question);

        return $rubric;
    }
}
