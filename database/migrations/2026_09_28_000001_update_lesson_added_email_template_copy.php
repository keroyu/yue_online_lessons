<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * New-lesson mail copy (004 FR-031): subject drops the course name, body says
 * 「立即觀看新小節」 now that {{classroom_url}} opens the lesson itself.
 *
 * The template is data, so a code change alone never reaches production, and
 * re-running the seeder would overwrite the owner's edits (D30). So: the
 * subject changes only while it is still the old default, and the body has
 * exactly one sentence swapped — everything else the owner wrote is kept.
 */
return new class extends Migration
{
    private const OLD_SUBJECT = '您擁有的課程「{{course_name}}」新增了小節：{{lesson_title}}';
    private const NEW_SUBJECT = '您擁有的課程新增了小節：「{{lesson_title}}」';
    private const OLD_LINE = '歡迎回來繼續學習：';
    private const NEW_LINE = '立即觀看新小節：';

    public function up(): void
    {
        $this->rewrite(self::OLD_SUBJECT, self::NEW_SUBJECT, self::OLD_LINE, self::NEW_LINE);
    }

    public function down(): void
    {
        $this->rewrite(self::NEW_SUBJECT, self::OLD_SUBJECT, self::NEW_LINE, self::OLD_LINE);
    }

    private function rewrite(string $fromSubject, string $toSubject, string $fromLine, string $toLine): void
    {
        $templates = DB::table('email_templates')
            ->where('event_type', 'lesson_added')
            ->get(['id', 'subject', 'body_md']);

        foreach ($templates as $template) {
            $changes = [];

            if ($template->subject === $fromSubject) {
                $changes['subject'] = $toSubject;
            }

            if ($template->body_md !== null && str_contains($template->body_md, $fromLine)) {
                $changes['body_md'] = str_replace($fromLine, $toLine, $template->body_md);
            }

            if ($changes) {
                DB::table('email_templates')->where('id', $template->id)
                    ->update($changes + ['updated_at' => now()]);
            }
        }
    }
};
