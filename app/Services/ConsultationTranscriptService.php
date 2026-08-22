<?php

namespace App\Services;

use App\Models\ConsultationNote;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Turning a Zoom VTT into a consultation record (011 US23).
 *
 * The business core of the feature: parse, anonymise, proofread, summarise.
 */
class ConsultationTranscriptService
{
    public const PROOFREAD_PROMPT = 'consultation_transcript_proofread';
    public const SUMMARY_PROMPT = 'consultation_summary';

    public const CONSULTANT_LABEL = '顧問';
    public const CUSTOMER_LABEL = '客戶';

    /** Roughly one prompt's worth of dialogue. Split on speaker boundaries, never mid-turn. */
    private const CHUNK_CHARS = 4000;

    /**
     * Below this ratio the model stopped proofreading and started summarising.
     * A quietly halved "transcript" is worse than one with typos in it, because
     * it still reads as complete (FR-108).
     */
    private const MIN_LENGTH_RATIO = 0.6;

    public function __construct(private OpenAiService $ai) {}

    /**
     * VTT → `講者: 內容` lines.
     *
     * Drops the WEBVTT header, cue numbers, timing lines and blank lines, and
     * merges consecutive lines from one speaker back into a single turn (the
     * transcriber breaks a sentence every few seconds).
     *
     * Every line split here uses `/u` and that modifier is load-bearing, not
     * decoration: without it `\R` also matches the raw byte 0x85, which occurs
     * *inside* countless CJK characters, so a Chinese transcript gets sliced
     * mid-character and every downstream json_encode fails on malformed UTF-8.
     */
    public function vttToDialogue(string $vtt): string
    {
        $lines = preg_split('/\R/u', trim($vtt)) ?: [];
        $turns = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, 'WEBVTT')) {
                continue;
            }

            // Cue timings and the bare sequence numbers that precede them.
            if (str_contains($line, '-->') || preg_match('/^\d+$/', $line)) {
                continue;
            }

            // NOTE / STYLE blocks and anything else the transcriber emits.
            if (preg_match('/^(NOTE|STYLE|REGION)\b/', $line)) {
                continue;
            }

            [$speaker, $text] = $this->splitSpeaker($line);

            $last = end($turns);

            if ($last !== false && $last['speaker'] === $speaker) {
                $turns[key($turns)]['text'] .= ($speaker === null ? ' ' : '') . $text;
                continue;
            }

            $turns[] = ['speaker' => $speaker, 'text' => $text];
        }

        return implode("\n", array_map(
            fn (array $turn) => $turn['speaker'] === null
                ? $turn['text']
                : $turn['speaker'] . ': ' . $turn['text'],
            $turns
        ));
    }

    /**
     * Map Zoom's display names onto 顧問 / 客戶 (FR-106).
     *
     * Mechanical, because who is who is a known fact — the lead's name and the
     * consultant's name are both sitting on the record. Guessing it would risk
     * inverting the whole document's attribution, which is the expensive error.
     * Anything left unmatched is handed to the proofreading pass.
     */
    public function normaliseSpeakers(string $dialogue, ConsultationNote $note): string
    {
        $map = [];

        // Both name columns, because the Zoom display name is whatever the
        // person typed into Zoom — it may match either one.
        $consultant = $note->consultant_id
            ? User::whereKey($note->consultant_id)->first(['nickname', 'real_name'])
            : null;

        foreach ([$consultant?->nickname, $consultant?->real_name] as $name) {
            if ($name) {
                $map[$this->normaliseName($name)] = self::CONSULTANT_LABEL;
            }
        }

        foreach ($this->customerNameCandidates($note) as $name) {
            $map[$this->normaliseName($name)] = self::CUSTOMER_LABEL;
        }

        $lines = preg_split('/\R/u', $dialogue) ?: [];

        $mapped = array_map(function (string $line) use ($map) {
            [$speaker, $text] = $this->splitSpeaker($line);

            if ($speaker === null) {
                return $line;
            }

            $key = $this->normaliseName($speaker);

            foreach ($map as $needle => $label) {
                if ($key === $needle || str_contains($key, $needle) || str_contains($needle, $key)) {
                    return $label . ': ' . $text;
                }
            }

            return $speaker . ': ' . $text;
        }, $lines);

        return implode("\n", $mapped);
    }

    /**
     * Proofread the dialogue in chunks, keeping every sentence (FR-107/FR-108).
     *
     * Returns the mechanical text untouched when AI is not configured.
     */
    public function proofread(string $dialogue, ConsultationNote $note): string
    {
        if (!$this->ai->isEnabled() || trim($dialogue) === '') {
            return $dialogue;
        }

        $output = [];

        foreach ($this->chunk($dialogue) as $chunk) {
            $result = $this->ai->respond(self::PROOFREAD_PROMPT, $chunk);

            if ($result === null) {
                $output[] = $chunk;
                continue;
            }

            if (mb_strlen($result) < mb_strlen($chunk) * self::MIN_LENGTH_RATIO) {
                Log::warning('Consultation transcript: proofread output was too short, keeping the mechanical text', [
                    'note_id'     => $note->id,
                    'input_chars' => mb_strlen($chunk),
                    'output_chars' => mb_strlen($result),
                ]);

                $output[] = $chunk;
                continue;
            }

            $output[] = $result;
        }

        return implode("\n", $output);
    }

    /**
     * Every name this customer might appear under, in order of authority
     * (011 FR-148).
     *
     * One list with one order, because two consumers read it for different
     * reasons: `normaliseSpeakers()` maps *all* of them onto the 客戶 label,
     * while `summarise()` takes the first as what to call this person. Kept
     * apart they would drift, and the summary would end up addressing someone
     * by a name the anonymiser does not recognise.
     *
     * The name they registered with wins: it is what they typed about
     * themselves for this consultation, and it is present for every lead —
     * the member profile may not exist yet at all.
     *
     * @return array<int, string>
     */
    public function customerNameCandidates(ConsultationNote $note): array
    {
        $customer = User::where('email', $note->email)->first(['nickname', 'real_name']);

        return array_values(array_filter([
            $note->lead?->name,
            $customer?->nickname,
            $customer?->real_name,
        ], fn (?string $name) => is_string($name) && trim($name) !== ''));
    }

    /**
     * Summarise one consultation (011 US23), addressed to a named person
     * (US29).
     *
     * Takes the note rather than the transcript string so both halves of the
     * context come off the same row — a transcript from one session with a name
     * from another is exactly the mix-up a second parameter invites.
     *
     * The name rides in the *input*, never the instructions: the instructions
     * are the owner's editable general rules, and who this particular customer
     * is is a per-session fact (FR-148). When nobody has a usable name the line
     * is absent entirely rather than empty — a placeholder would read to the
     * model as what this person is called.
     */
    public function summarise(ConsultationNote $note): ?string
    {
        $transcript = (string) $note->transcript;

        if (trim($transcript) === '') {
            return null;
        }

        $name = $this->customerNameCandidates($note)[0] ?? null;

        $input = $name === null
            ? $transcript
            : "客戶暱稱：{$name}\n\n{$transcript}";

        return $this->ai->respond(self::SUMMARY_PROMPT, $input);
    }

    /**
     * Split into chunks of roughly CHUNK_CHARS, always on a line boundary so a
     * single turn is never cut in half.
     *
     * @return array<int, string>
     */
    private function chunk(string $dialogue): array
    {
        $lines = preg_split('/\R/u', $dialogue) ?: [];
        $chunks = [];
        $current = '';

        foreach ($lines as $line) {
            if ($current !== '' && mb_strlen($current) + mb_strlen($line) > self::CHUNK_CHARS) {
                $chunks[] = $current;
                $current = '';
            }

            $current .= ($current === '' ? '' : "\n") . $line;
        }

        if (trim($current) !== '') {
            $chunks[] = $current;
        }

        return $chunks;
    }

    /**
     * @return array{0: ?string, 1: string} speaker (null when the line has none) and text
     */
    private function splitSpeaker(string $line): array
    {
        // A speaker label is short and precedes the first colon; a colon deep
        // into a sentence is punctuation, not a label.
        if (preg_match('/^([^:：]{1,30})[:：]\s*(.*)$/u', $line, $matches)) {
            return [trim($matches[1]), trim($matches[2])];
        }

        return [null, $line];
    }

    private function normaliseName(string $name): string
    {
        $name = mb_convert_kana($name, 'as');

        return mb_strtolower(preg_replace('/\s+/u', '', $name) ?? $name);
    }
}
