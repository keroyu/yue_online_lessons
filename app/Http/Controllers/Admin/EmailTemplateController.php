<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EmailTemplateRequest;
use App\Models\EmailTemplate;
use App\Models\SiteSetting;
use App\Services\HighTicketBookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmailTemplateController extends Controller
{
    private array $availableVariables = [
        'high_ticket_booking_confirmation' => [
            ['key' => '{{user_name}}', 'label' => '訪客姓名'],
            ['key' => '{{user_email}}', 'label' => '訪客 Email'],
            ['key' => '{{course_name}}', 'label' => '課程名稱'],
        ],
        'course_gifted' => [
            ['key' => '{{course_name}}', 'label' => '課程名稱'],
            ['key' => '{{course_description}}', 'label' => '課程描述'],
            ['key' => '{{app_url}}', 'label' => '網站網址'],
        ],
        'lesson_added' => [
            ['key' => '{{course_name}}', 'label' => '課程名稱'],
            ['key' => '{{lesson_title}}', 'label' => '小節標題'],
            ['key' => '{{classroom_url}}', 'label' => '教室連結'],
        ],
    ];

    public function index(): Response
    {
        $templates = EmailTemplate::orderBy('event_type')->get();

        return Inertia::render('Admin/EmailTemplates/Index', [
            'templates' => $templates,
            'notifyCc' => (string) SiteSetting::get(HighTicketBookingService::NOTIFY_CC_SETTING_KEY, ''),
            'notifyCcDefault' => implode(', ', HighTicketBookingService::DEFAULT_NOTIFY_CC),
        ]);
    }

    /**
     * Who gets CC'd on every high-ticket booking confirmation. Kept on this page
     * because it is Email routing, not payment/API config.
     */
    public function updateNotifyCc(Request $request): RedirectResponse
    {
        $request->validate([
            'notify_cc' => ['nullable', 'string', 'max:500', function ($attribute, $value, $fail) {
                foreach (HighTicketBookingService::parseRecipients((string) $value) as $email) {
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $fail("「{$email}」不是有效的 Email 格式");
                    }
                }
            }],
        ]);

        SiteSetting::set(
            HighTicketBookingService::NOTIFY_CC_SETTING_KEY,
            implode(', ', HighTicketBookingService::parseRecipients((string) $request->input('notify_cc', '')))
        );

        return redirect()
            ->route('admin.email-templates.index')
            ->with('success', '預約通知收件者已更新');
    }

    public function edit(EmailTemplate $template): Response
    {
        return Inertia::render('Admin/EmailTemplates/Edit', [
            'template' => $template,
            'availableVariables' => $this->availableVariables[$template->event_type] ?? [],
        ]);
    }

    public function update(EmailTemplateRequest $request, EmailTemplate $template): RedirectResponse
    {
        $template->update($request->validated());

        return redirect()
            ->route('admin.email-templates.index')
            ->with('success', '模板已更新');
    }
}
