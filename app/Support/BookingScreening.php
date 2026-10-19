<?php

namespace App\Support;

use Illuminate\Validation\Rule;

/**
 * The seven-question gate in front of the booking wizard (011 US24 / FR-123).
 *
 * One definition, on the server. The questions are shipped to the browser for
 * rendering — the scores are not (FR-124): a rubric in the page source is a
 * rubric with the answer key attached.
 *
 * Scoring is a lookup table, deliberately not a model call (D100). Seven
 * single-choice answers carry no language to interpret, and a model would buy
 * latency, cost and a score that can differ between two runs of the same
 * answers — which is exactly what you cannot have when somebody asks why they
 * were turned away.
 */
class BookingScreening
{
    /** Out of a possible 10. Q1–Q5 score up to 2 each; Q6/Q7 score nothing (FR-254). */
    public const PASS_SCORE = 5;

    /**
     * What a turned-away applicant reads when the course sets no copy of its
     * own (FR-128 wording, FR-259). Kept here, not in the wizard, so the
     * front end never carries a second copy that can drift.
     */
    public const DEFAULT_DECLINE_HTML = '感謝您的申請。根據您的現況，我們判斷現階段可能不是最適合安排一對一諮詢和推進下一步計劃的時機，因此此次先不安排預約。謝謝您的理解，祝您接下來的規劃順利。';

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
     *
     * `hint` (question level, optional) is a line of small print under the
     * title, shipped to the front with it (FR-254).
     *
     * `retired` takes an option off the form without orphaning the leads that
     * already chose it: it is no longer rendered or accepted on the screen
     * POST, but its label and scoring stay so old rows still read and re-score
     * the way they did (FR-252).
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
                //
                // Retired on 2026-10-17 (FR-252): applicants now have to name a band.
                'unsure'    => ['label' => '不確定，希望先了解方案內容與價格', 'score' => 1, 'cap' => 6, 'retired' => true],
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
                // Nobody who picked either has bought (0/6 on 2026-10-17) — FR-253.
                'diy'         => ['label' => '想先自己嘗試一段時間', 'score' => 0, 'veto' => true],
                'advice_only' => ['label' => '目前主要是想獲得一些建議，暫時沒有合作打算', 'score' => 0, 'veto' => true],
            ],
        ],
        // Q6 and Q7 score nothing on their own: the signal is the pair, and it
        // is not monotonic — somebody who has struggled alone for years has
        // felt the cost and buys, somebody a few months in who will not look
        // for help does not (FR-254, 使用者決策). See VETO_COMBOS.
        'screen_experience' => [
            'title'   => '你在經營社群這件事上，認真摸索多久了？',
            'hint'    => '一對一諮詢最能幫上忙的，是已經動手嘗試過、對社群經營有基本認知的人 —— 這樣我們才能針對你實際卡住的地方討論，而不是從零講起。',
            'options' => [
                'not_started' => ['label' => '還沒開始', 'score' => 0],
                'casual'      => ['label' => '偶爾發文，沒有特別花心思研究', 'score' => 0],
                'under_1y'    => ['label' => '認真摸索過，累積不到 1 年', 'score' => 0],
                '1_3y'        => ['label' => '認真摸索過，累積 1–3 年', 'score' => 0],
                'over_3y'     => ['label' => '認真摸索過，累積 3 年以上', 'score' => 0],
            ],
        ],
        'screen_learning' => [
            // Asks for the furthest step actually taken on the current problem,
            // not a habit — a habit question lets everyone pick the respectable
            // answer (FR-258). Options are ordered by depth.
            'title'   => '針對你現在卡住的問題，你做過最深入的一步是？',
            'options' => [
                'passive'     => ['label' => '平常滑到相關內容會看，但還沒特別針對這個問題去找', 'score' => 0],
                'searched'    => ['label' => '針對這個問題主動搜尋過教學、文章或影片，並試著照做', 'score' => 0],
                'engaged'     => ['label' => '加入相關社群發問交流，或聽過免費講座、直播、體驗課', 'score' => 0],
                'cheap_paid'  => ['label' => '買過專門書籍、講座，或數千元的課程', 'score' => 0],
                'paid_course' => ['label' => '上過中高價課程或找過顧問', 'score' => 0],
            ],
        ],
    ];

    /**
     * Vetoes that only hold in combination (FR-254 / D169).
     *
     * Each entry is one rule. `match` fires when every listed field's answer
     * is in its list; `unless`, when present, lifts it again if its own
     * `match` holds and the total reaches `min_score`. Any rule left firing
     * vetoes, exactly like an option's `veto` flag. Kept here, beside
     * QUESTIONS, so the rubric stays one table on the server and never travels
     * to the browser (FR-124).
     */
    public const VETO_COMBOS = [
        // A short time in, and never reached a community or a trial class —
        // unless they name a real budget and the rest of the answers are
        // strong (7+): that applicant is worth a conversation (使用者決策).
        [
            'match' => [
                'screen_experience' => ['not_started', 'casual', 'under_1y'],
                'screen_learning'   => ['passive', 'searched'],
            ],
            'unless' => [
                'match'     => ['screen_budget' => ['6k_10k', '10k_50k', '50k_100k', 'over_100k']],
                'min_score' => 7,
            ],
        ],
    ];

    /** The answer column names, which are also the request keys. */
    public static function fields(): array
    {
        return array_keys(self::QUESTIONS);
    }

    /**
     * Questions as the wizard renders them — titles and option labels only.
     *
     * `score` and `veto` are stripped here rather than in the controller: this
     * is the method every front end must go through, so there is one place to
     * be right about it (FR-124 / D101). Retired options are dropped (FR-252).
     *
     * @return array<int, array{field: string, title: string, hint?: string, options: array<int, array{value: string, label: string}>}>
     */
    public static function questionsForFront(): array
    {
        $out = [];

        foreach (self::QUESTIONS as $field => $question) {
            $options = self::activeOptions($question);

            $out[] = array_filter([
                'field'   => $field,
                'title'   => $question['title'],
                'hint'    => $question['hint'] ?? null,
                'options' => array_values(array_map(
                    fn ($value, $option) => ['value' => $value, 'label' => $option['label']],
                    array_keys($options),
                    $options,
                )),
            ], fn ($value) => $value !== null);
        }

        return $out;
    }

    /**
     * Validation rules for the answers.
     *
     * @param bool $required false on the booking submit, where a resumed draft
     *                       or a pre-feature lead legitimately carries none
     *                       (FR-129). It still accepts retired options:
     *                       a draft that cleared the gate before the option
     *                       was retired skips step 1 and could not fix it
     *                       (FR-252).
     */
    public static function rules(bool $required = true): array
    {
        $rules = [];

        foreach (self::QUESTIONS as $field => $question) {
            $options = $required ? self::activeOptions($question) : $question['options'];

            $rules[$field] = [
                $required ? 'required' : 'nullable',
                Rule::in(array_keys($options)),
            ];
        }

        return $rules;
    }

    /** The options a new applicant may still pick (FR-252). */
    private static function activeOptions(array $question): array
    {
        return array_filter($question['options'], fn ($option) => !($option['retired'] ?? false));
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

    /** An answer, or a pair of answers, that disqualifies whatever the total (FR-123 / FR-254). */
    public static function vetoed(array $answers): bool
    {
        foreach (self::QUESTIONS as $field => $question) {
            if (($question['options'][$answers[$field] ?? null]['veto'] ?? false) === true) {
                return true;
            }
        }

        foreach (self::VETO_COMBOS as $combo) {
            if (!self::matches($answers, $combo['match'])) {
                continue;
            }

            $unless = $combo['unless'] ?? null;
            $lifted = $unless !== null
                && self::matches($answers, $unless['match'])
                && self::score($answers) >= $unless['min_score'];

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
     * 011 FR-261 / D176) refuses only no budget or a short total — the same
     * PASS_SCORE, so switching strict off can only ever let more people through.
     */
    public static function passes(array $answers, bool $strict = true): bool
    {
        if (self::score($answers) < self::PASS_SCORE) {
            return false;
        }

        return $strict
            ? !self::vetoed($answers)
            : ($answers['screen_budget'] ?? null) !== 'none';
    }

    /** Routing to the chief starts here (011 FR-264); 7 and under may go to the others. */
    public const CHIEF_MIN_SCORE = 8;

    /** NT$6,000 and up. */
    public const CHIEF_BUDGETS = ['6k_10k', '10k_50k', '50k_100k', 'over_100k'];

    /** Under NT$6,000. `none` is refused outright, so it never reaches routing. */
    public const OTHERS_BUDGETS = ['under_6k'];

    /**
     * Whose slots this applicant is offered (011 US45 / FR-264): `chief`,
     * `others` or `all`. Kept beside the rubric so the bands and the budget
     * cut stay one table on the server; whether routing applies at all (more
     * than one consultant) is the slot service's call, not the rubric's.
     */
    public static function consultantPool(array $answers): string
    {
        if (!self::answered($answers)) {
            return 'all';
        }

        $score = self::score($answers);
        $budget = $answers['screen_budget'];

        return match (true) {
            $score >= self::CHIEF_MIN_SCORE && in_array($budget, self::CHIEF_BUDGETS, true) => 'chief',
            $score < self::CHIEF_MIN_SCORE && in_array($budget, self::OTHERS_BUDGETS, true) => 'others',
            default => 'all',
        };
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

    /** Only the screening answers, for handing to `update()` / `create()`. */
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
