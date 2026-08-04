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
        'high_ticket_booking_verify' => [
            ['key' => '{{user_name}}', 'label' => '申請人暱稱'],
            ['key' => '{{course_name}}', 'label' => '課程名稱'],
            ['key' => '{{confirm_url}}', 'label' => '確認連結（必放，否則對方無法完成預約）'],
            ['key' => '{{slot_time}}', 'label' => '所選時段（含長度）'],
            ['key' => '{{expires_at}}', 'label' => '保留到期時間（時:分）'],
        ],
        'high_ticket_booking_confirmation' => [
            ['key' => '{{user_name}}', 'label' => '訪客姓名'],
            ['key' => '{{user_email}}', 'label' => '訪客 Email'],
            ['key' => '{{course_name}}', 'label' => '課程名稱'],
            ['key' => '{{slot_time}}', 'label' => '諮詢時段'],
            ['key' => '{{consult_minutes}}', 'label' => '諮詢長度（分鐘）'],
            ['key' => '{{zoom_join_url}}', 'label' => 'Zoom 會議連結（未設定 Zoom 時為空）'],
        ],
        'high_ticket_booking_rescheduled' => [
            ['key' => '{{user_name}}', 'label' => '申請人暱稱'],
            ['key' => '{{user_email}}', 'label' => '申請人 Email'],
            ['key' => '{{course_name}}', 'label' => '課程名稱'],
            ['key' => '{{old_slot_time}}', 'label' => '原時段'],
            ['key' => '{{slot_time}}', 'label' => '新時段'],
            ['key' => '{{consult_minutes}}', 'label' => '諮詢長度（分鐘）'],
            ['key' => '{{zoom_join_url}}', 'label' => 'Zoom 會議連結（改期不會換連結）'],
        ],
        'high_ticket_booking_cancelled' => [
            ['key' => '{{user_name}}', 'label' => '申請人暱稱'],
            ['key' => '{{user_email}}', 'label' => '申請人 Email'],
            ['key' => '{{course_name}}', 'label' => '課程名稱'],
            ['key' => '{{slot_time}}', 'label' => '原訂時段'],
            ['key' => '{{course_url}}', 'label' => '課程銷售頁網址（讓對方可以重新預約）'],
        ],
        'high_ticket_slot_available' => [
            ['key' => '{{user_name}}', 'label' => '候補者暱稱'],
            ['key' => '{{course_name}}', 'label' => '課程名稱'],
            ['key' => '{{booking_url}}', 'label' => '回站選時段連結（候補者可直接跳到選時段，不必重填問卷）'],
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
        'lead_converted' => [
            ['key' => '{{user_name}}', 'label' => '客戶姓名'],
            ['key' => '{{course_name}}', 'label' => '課程名稱'],
            ['key' => '{{amount}}', 'label' => '成交金額（含千分位，不含幣別）'],
            ['key' => '{{classroom_url}}', 'label' => '教室連結'],
            ['key' => '{{app_url}}', 'label' => '網站網址'],
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
