<?php

namespace App\Support;

use Illuminate\Validation\Rule;

/**
 * The five-question gate in front of the booking wizard (011 US24 / FR-123).
 *
 * One definition, on the server. The questions are shipped to the browser for
 * rendering — the scores are not (FR-124): a rubric in the page source is a
 * rubric with the answer key attached.
 *
 * Scoring is a lookup table, deliberately not a model call (D100). Five
 * single-choice answers carry no language to interpret, and a model would buy
 * latency, cost and a score that can differ between two runs of the same
 * answers — which is exactly what you cannot have when somebody asks why they
 * were turned away.
 */
class BookingScreening
{
    /** Out of a possible 10. Five questions, 2 points each at best. */
    public const PASS_SCORE = 5;

    /**
     * Every question, option, label and score.
     *
     * Budget bands are tied to the actual plan prices (entry 8,888 / standard
     * 36,000 / flagship 58,000): full marks start at NT$10,000 because that is
     * where the main plan becomes affordable. `unsure` scores 1 rather than 0 —
     * the sales page hides its price, so "tell me the number first" is an honest
     * answer, not a cold one.
     *
     * `veto` overrides the total: no budget at all means no consultation, even
     * from somebody who is urgent, decisive and in pain (使用者決策).
     *
     * `cap` is the softer version of the same idea: the answer does not
     * disqualify, but it puts a ceiling on the total however good the other
     * four are (FR-172).
     */
    public const QUESTIONS = [
        'screen_timeline' => [
            'title'   => '你希望在多久內開始改善目前的問題？',
            'options' => [
                'immediate' => ['label' => '立即，希望 1 個月內開始', 'score' => 2],
                '1_3m'      => ['label' => '1–3 個月內', 'score' => 2],
                '3_6m'      => ['label' => '3–6 個月內', 'score' => 1],
                '6m_plus'   => ['label' => '6 個月以上', 'score' => 0],
                'exploring' => ['label' => '目前只是先了解，沒有明確時間表', 'score' => 0],
            ],
        ],
        'screen_budget' => [
            'title'   => '如果確認這項服務適合你，你目前可考慮投入的預算大約是多少？',
            'options' => [
                'over_100k' => ['label' => 'NT$100,000 以上', 'score' => 2],
                '50k_100k'  => ['label' => 'NT$50,000–100,000', 'score' => 2],
                '10k_50k'   => ['label' => 'NT$10,000–49,999', 'score' => 2],
                '6k_10k'    => ['label' => 'NT$6,000–9,999', 'score' => 1],
                'under_6k'  => ['label' => 'NT$5,999 以下', 'score' => 0],
                'none'      => ['label' => '目前沒有預算', 'score' => 0, 'veto' => true],
                // Still an honest answer to a page that hides its price, so it
                // scores and never blocks — but it is not yet a budget, and a
                // 高購買意願 badge on somebody who has not named one sends the
                // consultant in with an expectation the answers do not support
                // (FR-172, 使用者決策).
                //
                // Lowered 7 → 6 on 2026-09-05: both sit in 值得談 (5–7), so the
                // badge never moved. What moved is the ordering — a named 6 and
                // an unnamed 6 must not read as the same application, and at 7
                // the unnamed one outranked most of the named ones.
                'unsure'    => ['label' => '不確定，希望先了解方案內容與價格', 'score' => 1, 'cap' => 6],
            ],
        ],
        'screen_authority' => [
            'title'   => '關於這筆投入，你目前的決策狀態最接近哪一種？',
            'options' => [
                'self'           => ['label' => '我可以自行決定，只要確認適合就能開始', 'score' => 2],
                'discuss'        => ['label' => '我是主要決策者，但需要和伴侶／夥伴討論', 'score' => 2],
                'approval'       => ['label' => '需要其他人共同核准', 'score' => 1],
                'none'           => ['label' => '我目前沒有決策權', 'score' => 0],
                'not_considered' => ['label' => '還沒想過是否要投入', 'score' => 0],
            ],
        ],
        'screen_pain' => [
            'title'   => '如果接下來 3–6 個月都沒有改善這個問題，對你的影響有多大？',
            'options' => [
                'severe'   => ['label' => '影響非常大，已經造成明顯損失或壓力', 'score' => 2],
                'high'     => ['label' => '影響很大，希望盡快解決', 'score' => 2],
                'moderate' => ['label' => '有影響，但目前仍可接受', 'score' => 1],
                'low'      => ['label' => '影響不大', 'score' => 0],
                'curious'  => ['label' => '只是想提前了解', 'score' => 0],
            ],
        ],
        'screen_next_step' => [
            'title'   => '如果這次討論後，你認為方向適合，你最可能採取哪個下一步？',
            'options' => [
                'start_now'   => ['label' => '希望盡快確認合作方式並開始', 'score' => 2],
                'evaluate'    => ['label' => '願意認真評估方案與費用後決定', 'score' => 2],
                'compare'     => ['label' => '需要再比較其他選項', 'score' => 1],
                'diy'         => ['label' => '想先自己嘗試一段時間', 'score' => 0],
                'advice_only' => ['label' => '目前主要是想獲得一些建議，暫時沒有合作打算', 'score' => 0],
            ],
        ],
    ];

    /** The five column names, which are also the five request keys. */
    public static function fields(): array
    {
        return array_keys(self::QUESTIONS);
    }

    /**
     * Questions as the wizard renders them — titles and option labels only.
     *
     * `score` and `veto` are stripped here rather than in the controller: this
     * is the method every front end must go through, so there is one place to
     * be right about it (FR-124 / D101).
     *
     * @return array<int, array{field: string, title: string, options: array<int, array{value: string, label: string}>}>
     */
    public static function questionsForFront(): array
    {
        $out = [];

        foreach (self::QUESTIONS as $field => $question) {
            $out[] = [
                'field'   => $field,
                'title'   => $question['title'],
                'options' => array_values(array_map(
                    fn ($value, $option) => ['value' => $value, 'label' => $option['label']],
                    array_keys($question['options']),
                    $question['options'],
                )),
            ];
        }

        return $out;
    }

    /**
     * Validation rules for the five answers.
     *
     * @param bool $required false on the booking submit, where a resumed draft
     *                       or a pre-feature lead legitimately carries none
     *                       (FR-129)
     */
    public static function rules(bool $required = true): array
    {
        $rules = [];

        foreach (self::QUESTIONS as $field => $question) {
            $rules[$field] = [
                $required ? 'required' : 'nullable',
                Rule::in(array_keys($question['options'])),
            ];
        }

        return $rules;
    }

    /**
     * The total, then whatever ceiling the answers themselves impose (FR-172).
     *
     * The cap is applied here rather than at the banding, so the single stored
     * `screening_score` is already the honest number: `tier()`, the `N/10` on
     * the expanded row and any future report all read the same value, and none
     * of them has to remember to re-apply the rule.
     *
     * @param array<string, mixed> $answers
     */
    public static function score(array $answers): int
    {
        $total = 0;
        $cap = null;

        foreach (self::QUESTIONS as $field => $question) {
            $option = $question['options'][$answers[$field] ?? null] ?? null;

            $total += $option['score'] ?? 0;

            // Lowest ceiling wins, so a second capping answer can only tighten.
            if (isset($option['cap'])) {
                $cap = $cap === null ? $option['cap'] : min($cap, $option['cap']);
            }
        }

        return $cap === null ? $total : min($total, $cap);
    }

    /** An answer that disqualifies on its own, whatever the total (FR-123). */
    public static function vetoed(array $answers): bool
    {
        foreach (self::QUESTIONS as $field => $question) {
            if (($question['options'][$answers[$field] ?? null]['veto'] ?? false) === true) {
                return true;
            }
        }

        return false;
    }

    public static function passes(array $answers): bool
    {
        return !self::vetoed($answers) && self::score($answers) >= self::PASS_SCORE;
    }

    /** True when the payload carries a complete set of answers to judge. */
    public static function answered(array $answers): bool
    {
        foreach (self::fields() as $field) {
            if (blank($answers[$field] ?? null)) {
                return false;
            }
        }

        return true;
    }

    /** Only the five answers, for handing to `update()` / `create()`. */
    public static function only(array $answers): array
    {
        return array_intersect_key($answers, array_flip(self::fields()));
    }

    /**
     * Admin-side banding (FR-131). Display only — the pass mark is PASS_SCORE,
     * not the boundary between these labels.
     */
    public static function tier(?int $score): string
    {
        return match (true) {
            $score === null => 'unknown',
            $score >= 8     => 'hot',
            $score >= 5     => 'warm',
            default         => 'cold',
        };
    }
}
