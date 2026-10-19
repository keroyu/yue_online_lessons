<?php

namespace App\Support;

use Illuminate\Validation\Rule;

/**
 * The qualifying gate in front of the booking wizard (011 US24 / FR-123).
 *
 * One definition, on the server — since US46 that definition is data, edited
 * on 1v1 漏斗設定 and read through ScreeningRubric (FR-265). The questions are
 * shipped to the browser for rendering — the scores are not (FR-124): a rubric
 * in the page source is a rubric with the answer key attached.
 *
 * Scoring is a lookup table, deliberately not a model call (D100). Seven
 * single-choice answers carry no language to interpret, and a model would buy
 * latency, cost and a score that can differ between two runs of the same
 * answers — which is exactly what you cannot have when somebody asks why they
 * were turned away.
 */
class BookingScreening
{
    /**
     * What a turned-away applicant reads when the course sets no copy of its
     * own (FR-128 wording, FR-259). Kept here, not in the wizard, so the
     * front end never carries a second copy that can drift.
     */
    public const DEFAULT_DECLINE_HTML = '感謝您的申請。根據您的現況，我們判斷現階段可能不是最適合安排一對一諮詢和推進下一步計劃的時機，因此此次先不安排預約。謝謝您的理解，祝您接下來的規劃順利。';

    /** Every question, retired ones included (FR-252), in display order. */
    public static function questions(): array
    {
        return ScreeningRubric::current()['questions'];
    }

    /** The question keys a new applicant answers — which are also the request keys. */
    public static function fields(): array
    {
        return array_column(self::activeQuestions(), 'key');
    }

    /** Every key an answer may be stored under, retired questions included. */
    public static function allFields(): array
    {
        return array_column(self::questions(), 'key');
    }

    public static function passScore(): int
    {
        return (int) ScreeningRubric::current()['pass_score'];
    }

    public static function maxScore(): int
    {
        return ScreeningRubric::maxScore();
    }

    /**
     * Questions as the wizard renders them — titles and option labels only.
     *
     * `score` and `veto` are stripped here rather than in the controller: this
     * is the method every front end must go through, so there is one place to
     * be right about it (FR-124 / D101). Retired items are dropped (FR-252).
     *
     * @return array<int, array{field: string, title: string, hint?: string, options: array<int, array{value: string, label: string}>}>
     */
    public static function questionsForFront(): array
    {
        $out = [];

        foreach (self::activeQuestions() as $question) {
            $out[] = array_filter([
                'field'   => $question['key'],
                'title'   => $question['title'],
                'hint'    => filled($question['hint'] ?? null) ? $question['hint'] : null,
                'options' => array_map(
                    fn ($option) => ['value' => $option['key'], 'label' => $option['label']],
                    self::activeOptions($question),
                ),
            ], fn ($value) => $value !== null);
        }

        return $out;
    }

    /**
     * Validation rules for the answers.
     *
     * @param bool $required false on the booking submit, where a resumed draft
     *                       or a pre-feature lead legitimately carries none
     *                       (FR-129). It still accepts retired questions and
     *                       options: a draft that cleared the gate before they
     *                       were retired skips step 1 and could not fix it
     *                       (FR-252).
     */
    public static function rules(bool $required = true): array
    {
        $rules = [];
        $questions = $required ? self::activeQuestions() : self::questions();

        foreach ($questions as $question) {
            $options = $required ? self::activeOptions($question) : $question['options'];

            $rules[$question['key']] = [
                $required ? 'required' : 'nullable',
                Rule::in(array_column($options, 'key')),
            ];
        }

        return $rules;
    }

    /**
     * The total, then whatever ceiling the answers themselves impose (FR-172).
     *
     * The cap is applied here rather than at the banding, so the single stored
     * `screening_score` is already the honest number.
     *
     * @param array<string, mixed> $answers
     */
    public static function score(array $answers): int
    {
        $total = 0;
        $cap = null;

        foreach (self::questions() as $question) {
            $option = self::option($question, $answers[$question['key']] ?? null);

            $total += (int) ($option['score'] ?? 0);

            // Lowest ceiling wins, so a second capping answer can only tighten.
            if (isset($option['cap'])) {
                $cap = $cap === null ? (int) $option['cap'] : min($cap, (int) $option['cap']);
            }
        }

        return $cap === null ? $total : min($total, $cap);
    }

    /**
     * An answer, or a combination of answers, that disqualifies whatever the
     * total (FR-123 / FR-254). `always` options refuse under either mode;
     * `strict` options and the combination rules only when screening strictly
     * (FR-261 / FR-265).
     */
    public static function vetoed(array $answers, bool $strict = true): bool
    {
        foreach (self::questions() as $question) {
            $veto = self::option($question, $answers[$question['key']] ?? null)['veto'] ?? 'none';

            if ($veto === 'always' || ($strict && $veto === 'strict')) {
                return true;
            }
        }

        if (!$strict) {
            return false;
        }

        foreach (ScreeningRubric::current()['combos'] as $combo) {
            if (!self::matches($answers, $combo['match'])) {
                continue;
            }

            $unless = $combo['unless'] ?? null;
            $lifted = $unless !== null
                && self::matches($answers, $unless['match'])
                && self::score($answers) >= (int) $unless['min_score'];

            if (!$lifted) {
                return true;
            }
        }

        return false;
    }

    /** Every listed field's answer is one of its listed values. */
    private static function matches(array $answers, array $conditions): bool
    {
        foreach ($conditions as $field => $values) {
            if (!in_array($answers[$field] ?? null, $values, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Strict is the full rubric. Lenient (a course with strict screening off,
     * 011 FR-261 / D176) refuses only `always` vetoes or a short total — the
     * same pass score, so switching strict off can only ever let more through.
     */
    public static function passes(array $answers, bool $strict = true): bool
    {
        return self::score($answers) >= self::passScore() && !self::vetoed($answers, $strict);
    }

    /**
     * Whose slots this applicant is offered (011 US45 / FR-264): `chief`,
     * `others` or `all`, from the rubric's routing rules. A rule that is off,
     * or points at a retired question, simply does not match. Whether routing
     * applies at all (more than one consultant) is the slot service's call.
     */
    public static function consultantPool(array $answers): string
    {
        if (!self::answered($answers)) {
            return 'all';
        }

        $routing = ScreeningRubric::current()['routing'];
        $score = self::score($answers);

        if (self::routes($routing['chief'] ?? null, $answers) && $score >= (int) $routing['chief']['min_score']) {
            return 'chief';
        }

        if (self::routes($routing['others'] ?? null, $answers) && $score <= (int) $routing['others']['max_score']) {
            return 'others';
        }

        return 'all';
    }

    private static function routes(?array $rule, array $answers): bool
    {
        if (!$rule || !($rule['enabled'] ?? false) || !in_array($rule['question'] ?? null, self::fields(), true)) {
            return false;
        }

        return in_array($answers[$rule['question']] ?? null, $rule['options'] ?? [], true);
    }

    /** True when the payload carries an answer to every active question. */
    public static function answered(array $answers): bool
    {
        foreach (self::fields() as $field) {
            if (blank($answers[$field] ?? null)) {
                return false;
            }
        }

        return true;
    }

    /** Only the screening answers, for storing in `screen_answers`. */
    public static function only(array $answers): array
    {
        return array_filter(
            array_intersect_key($answers, array_flip(self::allFields())),
            fn ($value) => filled($value),
        );
    }

    /** One option of a question by key, retired ones included. */
    public static function option(array $question, mixed $key): ?array
    {
        if ($key === null) {
            return null;
        }

        foreach ($question['options'] as $option) {
            if ($option['key'] === $key) {
                return $option;
            }
        }

        return null;
    }

    private static function activeQuestions(): array
    {
        return array_values(array_filter(self::questions(), fn ($q) => !($q['retired'] ?? false)));
    }

    /** The options a new applicant may still pick (FR-252). */
    private static function activeOptions(array $question): array
    {
        return array_values(array_filter($question['options'], fn ($o) => !($o['retired'] ?? false)));
    }

    /**
     * Admin-side banding (FR-131). Display only — the pass mark is `pass_score`,
     * not the boundary between these labels.
     */
    public static function tier(?int $score): string
    {
        $tiers = ScreeningRubric::current()['tiers'];

        return match (true) {
            $score === null               => 'unknown',
            $score >= (int) $tiers['hot']  => 'hot',
            $score >= (int) $tiers['warm'] => 'warm',
            default                       => 'cold',
        };
    }
}
