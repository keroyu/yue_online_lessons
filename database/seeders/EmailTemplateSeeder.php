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
        ];

        foreach ($templates as $template) {
            EmailTemplate::firstOrCreate(
                ['event_type' => $template['event_type']],
                $template
            );
        }
    }
}
