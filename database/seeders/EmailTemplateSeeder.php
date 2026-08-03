<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name' => '客製服務預約確認',
                'event_type' => 'high_ticket_booking_confirmation',
                'subject' => '【預約確認】{{course_name}} — 感謝您的預約',
                'body_md' => "您好 {{user_name}}，\n\n感謝您預約「{{course_name}}」的 1v1 面談服務。\n\n我們已收到您的預約申請，將盡快與您聯繫確認面談時間。\n\n預約資訊：\n- 姓名：{{user_name}}\n- Email：{{user_email}}\n- 課程：{{course_name}}\n\n若有任何問題，請直接回覆此信。\n\n經營者時間銀行",
            ],
            [
                'name' => '課程贈禮通知',
                'event_type' => 'course_gifted',
                'subject' => '您已獲得課程：{{course_name}}',
                'body_md' => "您好，\n\n您已獲得課程「{{course_name}}」的學習權限。\n\n{{course_description}}\n\n請登入帳號後，至「我的課程」查看：\n{{app_url}}/member/learning\n\n經營者時間銀行",
            ],
            [
                'name' => '課程新增小節通知',
                'event_type' => 'lesson_added',
                'subject' => '您擁有的課程「{{course_name}}」新增了小節：{{lesson_title}}',
                'body_md' => "您好，\n\n您擁有的課程「{{course_name}}」新增了小節：\n「{{lesson_title}}」\n\n歡迎回來繼續學習：\n{{classroom_url}}\n\n經營者時間銀行",
            ],
            [
                'name' => '客製服務新時段通知',
                'event_type' => 'high_ticket_slot_available',
                'subject' => '【新時段釋出】{{course_name}} 預約面談',
                'body_md' => "Hi {{user_name}}，\n\n感謝您之前預約 {{course_name}}。\n\n我們剛釋出了新的面談時段，歡迎重新預約！\n\n如有任何問題，歡迎回覆此信聯繫。",
            ],
            [
                'name' => '顧問成交開通通知',
                'event_type' => 'lead_converted',
                'subject' => '【開通完成】{{course_name}} 已可開始學習',
                'body_md' => "{{user_name}} 您好，\n\n已收到您的款項，「{{course_name}}」的學習權限已為您開通。\n\n成交金額：NT$ {{amount}}\n\n登入方式：直接用**這封信的收件 email** 到網站登入，系統會寄一組驗證碼給您，不需要設定密碼。\n\n登入頁面：\n{{app_url}}/login\n\n登入後即可開始上課：\n{{classroom_url}}\n\n若有任何問題，直接回覆此信即可。\n\n經營者時間銀行",
            ],
        ];

        foreach ($templates as $template) {
            EmailTemplate::updateOrCreate(
                ['event_type' => $template['event_type']],
                $template
            );
        }
    }
}
