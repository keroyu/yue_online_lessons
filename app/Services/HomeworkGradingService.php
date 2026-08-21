<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\Comment;

/**
 * Turns one student submission into a grading draft (003 US10).
 *
 * All the judgement lives in the prompt row, which the owner edits in
 * `/admin/settings/ai`; what stays here is the part the prompt cannot do for
 * itself — deciding what the model is allowed to see, and in what shape.
 */
class HomeworkGradingService
{
    public const PROMPT = 'homework_grading_draft';

    /** Per section. A long handout must not be able to push out the submission. */
    private const MAX_SECTION_CHARS = 8000;

    /** Enough for "what did I already tell them" without replaying the whole thread. */
    private const MAX_EXCHANGE_REPLIES = 6;

    /** Last section, so it is the freshest thing in the model's context. */
    private const NOTE_HEADING = '## 講師補充指示';

    private const TEACHER_LABEL = '老師';
    private const STUDENT_LABEL = '學員';

    public function __construct(private OpenAiService $ai) {}

    /**
     * Null means "not configured / nothing came back" — the caller turns that
     * into a 422. A genuine API failure still throws (OpenAiService's contract).
     */
    public function draft(Assignment $assignment, Comment $submission, ?string $note = null): ?string
    {
        return $this->ai->respond(self::PROMPT, $this->buildInput($assignment, $submission, $note));
    }

    /**
     * The four fixed sections of FR-020, plus the optional teacher note.
     *
     * Students appear as 「學員」 and nothing else: a nickname or an email in the
     * prompt buys no better grading and sends PII somewhere it never had to go.
     */
    private function buildInput(Assignment $assignment, Comment $submission, ?string $note): string
    {
        $sections = [
            '## 講義'          => $assignment->handout_md,
            '## 作業題目'      => $assignment->question_md,
            '## 學員提交'      => $submission->content,
            '## 先前的批改往返' => $this->exchange($submission),
        ];

        $parts = [];

        foreach ($sections as $heading => $body) {
            $body = trim((string) $body);
            $parts[] = $heading . "\n" . ($body === '' ? '（未提供）' : $this->truncate($body));
        }

        // Dropped entirely when absent rather than sent as （未提供） like the
        // four above: an empty instruction is not a section the model should
        // reason about, and leaving it out keeps the prompt identical to what
        // it was before this field existed (FR-024).
        $note = trim((string) $note);

        if ($note !== '') {
            $parts[] = self::NOTE_HEADING . "\n" . $this->truncate($note);
        }

        return implode("\n\n", $parts);
    }

    private function exchange(Comment $submission): string
    {
        $replies = $submission->replies()
            ->with('user')
            ->get()
            ->take(self::MAX_EXCHANGE_REPLIES);

        return $replies
            ->map(fn (Comment $reply) => sprintf(
                '%s: %s',
                $reply->user?->isAdmin() ? self::TEACHER_LABEL : self::STUDENT_LABEL,
                trim((string) $reply->content)
            ))
            ->implode("\n");
    }

    private function truncate(string $text): string
    {
        if (mb_strlen($text) <= self::MAX_SECTION_CHARS) {
            return $text;
        }

        return mb_substr($text, 0, self::MAX_SECTION_CHARS) . "\n\n（內容過長，已截斷）";
    }
}
