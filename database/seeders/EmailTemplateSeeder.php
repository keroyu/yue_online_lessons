<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        foreach (self::templates() as $template) {
            EmailTemplate::updateOrCreate(
                ['event_type' => $template['event_type']],
                $template
            );
        }
    }

    /**
     * The canonical set, public so a migration can install any that are missing
     * without a second copy of the bodies living somewhere else.
     *
     * Needed because seeders only ever run on a fresh install: a template added
     * here reaches production only if something else puts it there.
     *
     * @return array<int, array<string, string>>
     */
    public static function templates(): array
    {
        return [
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
            [
                'name' => '客製服務預約已改期',
                'event_type' => 'high_ticket_booking_rescheduled',
                'subject' => '【時段已更新】{{course_name}} — {{slot_time}}',
                'body_md' => "您好 {{user_name}}，\n\n您的「{{course_name}}」1v1 諮詢時段已更新：\n\n- 原時段：{{old_slot_time}}\n- **新時段：{{slot_time}}**\n- 長度：{{consult_minutes}} 分鐘\n\n會議連結不變，原本的連結仍然可用：\n{{zoom_join_url}}\n\n本信附有行事曆檔案，開啟後即可更新您日曆上的行程時間。\n\n若這個時間不方便，或需要取消，請直接回覆此信告訴我們。\n\n經營者時間銀行",
            ],
            [
                'name' => '客製服務面談提醒',
                'event_type' => 'high_ticket_consultation_reminder',
                'subject' => '【明天見】{{course_name}} 1v1 諮詢 — {{slot_time}}',
                'body_md' => "您好 {{user_name}}，\n\n提醒您，「{{course_name}}」的 1v1 諮詢就在**明天**：\n\n- 時段：{{slot_time}}\n- 長度：{{consult_minutes}} 分鐘\n\n會議連結：\n{{zoom_join_url}}\n\n請準時出席，並提前幾分鐘進入會議室確認網路與麥克風。時段是為您單獨保留的，遲到會直接壓縮我們可以談的時間。\n\n若臨時無法出席，請盡快來信 {{support_email}} 告知，讓我們把時段留給其他人。\n\n明天見。\n\n經營者時間銀行",
            ],
            [
                'name' => '申請未完成提醒',
                'event_type' => 'high_ticket_application_resume',
                'subject' => '【還差幾步】你的 {{course_name}} 1v1 諮詢申請尚未完成',
                'body_md' => "您好 {{user_name}}，\n\n你通過了「{{course_name}}」1v1 諮詢的資格審核，但申請還沒完成 —— 只差填寫聯絡方式與想討論的問題，再選一個時段。\n\n從這裡接著填，先前的回答都幫你留著：\n{{booking_url}}\n\n名額有限，時段是先完成申請的人先選。\n\n如果目前不打算繼續，這封信可以直接忽略，我們不會再提醒。\n\n經營者時間銀行",
            ],
            [
                'name' => '客製服務預約已取消',
                'event_type' => 'high_ticket_booking_cancelled',
                'subject' => '【預約已取消】{{course_name}} — {{slot_time}}',
                'body_md' => "您好 {{user_name}}，\n\n您原訂於 {{slot_time}} 的「{{course_name}}」1v1 諮詢已取消，該場次的會議連結同時失效。\n\n本信附有行事曆取消檔案，開啟後即可從您的日曆移除這筆行程。\n\n若想重新安排時間，歡迎再次預約：\n{{course_url}}\n\n或直接回覆此信與我們聯繫。\n\n經營者時間銀行",
            ],
            [
                'name' => '預約婉拒通知',
                'event_type' => 'high_ticket_booking_declined',
                'subject' => '【關於您的 1v1 諮詢申請】{{course_name}}',
                'body_md' => "您好 {{user_name}}，\n\n關於您原訂於 {{slot_time}} 的「{{course_name}}」1v1 諮詢，我們在複查申請內容後決定不安排這次面談，該場次的會議連結同時失效。\n\n一對一諮詢很吃時機。從您目前的規劃與準備來看，現在談能幫上的忙有限，我們認為還不是最好的時候，因此這次容我們婉拒。\n\n本信附有行事曆取消檔案，開啟後即可從您的日曆移除這筆行程。\n\n日後若您的規劃更具體，歡迎再與我們聯繫。\n\n經營者時間銀行",
            ],
        ];
    }
}
