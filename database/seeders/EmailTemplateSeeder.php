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
                'name' => '客製服務預約待確認',
                'event_type' => 'high_ticket_booking_verify',
                'subject' => '【請於 1 小時內確認】{{course_name}} 預約時段保留中',
                'body_md' => "您好 {{user_name}}，\n\n我們已收到您的「{{course_name}}」1v1 諮詢申請，時段正**暫時保留**中：\n\n- 時段：{{slot_time}}\n- 保留到：今天 {{expires_at}}\n\n請點擊下方連結完成確認，時段才會正式為您保留：\n\n{{confirm_url}}\n\n**超過 1 小時未確認，時段會自動釋出給其他人。**\n\n若這不是您本人申請的，忽略這封信即可。\n\n經營者時間銀行",
            ],
            [
                'name' => '客製服務預約確認',
                'event_type' => 'high_ticket_booking_confirmation',
                'subject' => '【預約完成】{{course_name}} — {{slot_time}}',
                'body_md' => "您好 {{user_name}}，\n\n您的「{{course_name}}」1v1 諮詢已完成預約。\n\n預約資訊：\n- 時段：{{slot_time}}\n- 長度：{{consult_minutes}} 分鐘\n- Email：{{user_email}}\n\n會議連結：\n{{zoom_join_url}}\n\n請在諮詢時間前把我們寄給您的資料看完，我們才有時間談真正重要的事。\n\n若需要改期，請直接回覆此信；**無故不出席將永久列入黑名單。**\n\n經營者時間銀行",
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
                'body_md' => "Hi {{user_name}}，\n\n感謝您之前申請 {{course_name}}。\n\n我們剛釋出了新的面談時段，您可以直接從下方連結選定時間，先前填寫的資料都還在，不需要重填：\n\n{{booking_url}}\n\n時段為先到先選，選定後請記得收信完成確認。\n\n如有任何問題，歡迎回覆此信聯繫。",
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
