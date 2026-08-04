<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Seed the two booking-change templates (011 US14 / FR-052 / D54).
 *
 * These live in EmailTemplateSeeder too, but production only ever runs
 * `migrate` — shipping them in the seeder alone would put the feature live
 * dead: the mail would find no template and leave a log warning instead.
 *
 * The mirror image of 2026_08_05_000005, which had to *append* to a template
 * the owner may have edited. These are brand-new event types, so there is
 * nothing to overwrite; the only requirement is not to insert twice.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->templates() as $template) {
            // Not insertOrIgnore: `event_type` carries a plain index, not a
            // unique one (see Schema notes), so there is no constraint for
            // IGNORE to trip over and a re-run would happily add a duplicate.
            $exists = DB::table('email_templates')
                ->where('event_type', $template['event_type'])
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('email_templates')->insert(array_merge($template, [
                'body_type'  => 'markdown',
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    public function down(): void
    {
        DB::table('email_templates')
            ->whereIn('event_type', array_column($this->templates(), 'event_type'))
            ->delete();
    }

    /** @return array<int, array<string, string>> */
    private function templates(): array
    {
        return [
            [
                'name'       => '客製服務預約已改期',
                'event_type' => 'high_ticket_booking_rescheduled',
                'subject'    => '【時段已更新】{{course_name}} — {{slot_time}}',
                'body_md'    => "您好 {{user_name}}，\n\n您的「{{course_name}}」1v1 諮詢時段已更新：\n\n- 原時段：{{old_slot_time}}\n- **新時段：{{slot_time}}**\n- 長度：{{consult_minutes}} 分鐘\n\n會議連結不變，原本的連結仍然可用：\n{{zoom_join_url}}\n\n本信附有行事曆檔案，開啟後即可更新您日曆上的行程時間。\n\n若這個時間不方便，或需要取消，請直接回覆此信告訴我們。\n\n經營者時間銀行",
            ],
            [
                'name'       => '客製服務預約已取消',
                'event_type' => 'high_ticket_booking_cancelled',
                'subject'    => '【預約已取消】{{course_name}} — {{slot_time}}',
                'body_md'    => "您好 {{user_name}}，\n\n您原訂於 {{slot_time}} 的「{{course_name}}」1v1 諮詢已取消，該場次的會議連結同時失效。\n\n本信附有行事曆取消檔案，開啟後即可從您的日曆移除這筆行程。\n\n若想重新安排時間，歡迎再次預約：\n{{course_url}}\n\n或直接回覆此信與我們聯繫。\n\n經營者時間銀行",
            ],
        ];
    }
};
