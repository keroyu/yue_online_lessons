<?php

namespace App\Support;

use App\Models\HighTicketLead;
use App\Models\SiteSetting;
use Illuminate\Support\Str;

/**
 * The screening rubric as data (011 US46 / FR-265): questions, options,
 * scores, vetoes, combination rules and consultant routing, edited on the
 * 1v1 漏斗設定 page and stored as one JSON row in `site_settings`.
 *
 * Until somebody saves, `default()` is the rubric — the exact rules the code
 * carried before this page existed — so a fresh install and the test suite
 * need no seeding, and "nothing configured" can never mean "nothing screened".
 *
 * Shape (arrays are ordered; order is display order):
 *   pass_score, tiers{hot, warm},
 *   questions[{key, title, hint?, retired?, options[{key, label, score, veto, cap?, retired?}]}],
 *   combos[{match{qkey: [okey]}, unless?{match, min_score}}],
 *   routing{chief{enabled, min_score, question, options[]}, others{enabled, max_score, question, options[]}}
 *
 * `veto` is one of none / strict / always: `always` refuses under lenient
 * screening too, `strict` only when the course screens strictly (FR-261).
 */
class ScreeningRubric
{
    public const SETTING_KEY = 'booking_screening_rubric';

    public const VETO_MODES = ['none', 'strict', 'always'];

    /** Reserved by the wizard's scope acknowledgement (FR-165). */
    private const RESERVED_KEYS = ['screen_ack'];

    /**
     * Memoised on the container rather than in a static, so it lives exactly
     * as long as the application instance — one request, one test.
     */
    private const MEMO = 'screening.rubric';

    public static function current(): array
    {
        if (app()->bound(self::MEMO)) {
            return app(self::MEMO);
        }

        $stored = SiteSetting::get(self::SETTING_KEY);
        $decoded = is_string($stored) ? json_decode($stored, true) : null;
        $rubric = is_array($decoded) ? $decoded : self::default();

        app()->instance(self::MEMO, $rubric);

        return $rubric;
    }

    /** Drop the memoised copy, after a save. */
    public static function forget(): void
    {
        app()->forgetInstance(self::MEMO);
    }

    /**
     * Persist a rubric posted from the settings page.
     *
     * New questions and options arrive with `key: null` and get one here, so a
     * key is never chosen by the browser and never changes once it exists.
     * Anything the page dropped that somebody has already answered comes back
     * as retired instead of vanishing, so old leads keep their wording (FR-252).
     */
    public static function save(array $rubric): array
    {
        $previous = self::current();
        $used = self::answeredKeys();

        $rubric['questions'] = self::assignKeys($rubric['questions']);
        $rubric['questions'] = self::keepAnswered($rubric['questions'], $previous['questions'], $used);
        $rubric['version'] = 1;

        SiteSetting::set(self::SETTING_KEY, json_encode($rubric, JSON_UNESCAPED_UNICODE));
        self::forget();

        return self::current();
    }

    /** Best total the active questions allow (shown as the 滿分). */
    public static function maxScore(?array $rubric = null): int
    {
        $rubric ??= self::current();
        $total = 0;

        foreach ($rubric['questions'] as $question) {
            if ($question['retired'] ?? false) {
                continue;
            }

            $scores = array_map(
                fn ($o) => (int) $o['score'],
                array_filter($question['options'], fn ($o) => !($o['retired'] ?? false)),
            );

            $total += $scores === [] ? 0 : max($scores);
        }

        return $total;
    }

    /**
     * Every question key and question.option key that appears in a stored
     * answer — what may no longer be hard-deleted.
     *
     * @return array{questions: array<string, true>, options: array<string, true>}
     */
    public static function answeredKeys(): array
    {
        $questions = [];
        $options = [];

        HighTicketLead::whereNotNull('screen_answers')
            ->select(['id', 'screen_answers'])
            ->chunkById(500, function ($leads) use (&$questions, &$options) {
                foreach ($leads as $lead) {
                    foreach ((array) $lead->screen_answers as $q => $o) {
                        if ($o === null || $o === '') {
                            continue;
                        }
                        $questions[$q] = true;
                        $options["{$q}.{$o}"] = true;
                    }
                }
            });

        return ['questions' => $questions, 'options' => $options];
    }

    private static function assignKeys(array $questions): array
    {
        $taken = array_filter(array_column($questions, 'key'));

        foreach ($questions as &$question) {
            if (blank($question['key'] ?? null)) {
                do {
                    $key = 'screen_' . Str::lower(Str::random(6));
                } while (in_array($key, $taken, true) || in_array($key, self::RESERVED_KEYS, true));

                $question['key'] = $taken[] = $key;
            }

            $optionKeys = array_filter(array_column($question['options'], 'key'));

            foreach ($question['options'] as &$option) {
                if (blank($option['key'] ?? null)) {
                    do {
                        $key = Str::lower(Str::random(6));
                    } while (in_array($key, $optionKeys, true));

                    $option['key'] = $optionKeys[] = $key;
                }
            }
            unset($option);
        }
        unset($question);

        return $questions;
    }

    /** Re-add, as retired, whatever the page removed but somebody answered. */
    private static function keepAnswered(array $questions, array $previous, array $used): array
    {
        $byKey = [];
        foreach ($questions as $i => $question) {
            $byKey[$question['key']] = $i;
        }

        foreach ($previous as $old) {
            $qkey = $old['key'];

            if (!isset($byKey[$qkey])) {
                if (isset($used['questions'][$qkey])) {
                    $old['retired'] = true;
                    $old['options'] = array_values(array_filter(
                        $old['options'],
                        fn ($o) => isset($used['options']["{$qkey}.{$o['key']}"]),
                    ));
                    $questions[] = $old;
                }

                continue;
            }

            $current = &$questions[$byKey[$qkey]];
            $present = array_column($current['options'], 'key');

            foreach ($old['options'] as $option) {
                if (!in_array($option['key'], $present, true) && isset($used['options']["{$qkey}.{$option['key']}"])) {
                    $option['retired'] = true;
                    $current['options'][] = $option;
                }
            }
            unset($current);
        }

        return array_values($questions);
    }

    /**
     * The rules as they stood when this page was introduced — formerly the
     * QUESTIONS / VETO_COMBOS / PASS_SCORE constants on BookingScreening and
     * the US45 routing constants. Comments on why each value is what it is
     * live in the spec (011 FR-123, FR-172, FR-252–254, FR-258, FR-264).
     */
    public static function default(): array
    {
        $o = fn (string $key, string $label, int $score, string $veto = 'none', array $extra = []) => array_merge(
            ['key' => $key, 'label' => $label, 'score' => $score, 'veto' => $veto],
            $extra,
        );

        return [
            'version'    => 1,
            'pass_score' => 5,
            'tiers'      => ['hot' => 8, 'warm' => 5],
            'questions'  => [
                [
                    'key'     => 'screen_timeline',
                    'title'   => '你希望在多久內開始改善目前的問題？',
                    'options' => [
                        $o('immediate', '立即，希望 1 個月內開始', 2),
                        $o('1_3m', '1–3 個月內', 2),
                        $o('3_6m', '3–6 個月內', 1),
                        $o('6m_plus', '6 個月以上', 0),
                        $o('exploring', '目前只是先了解，沒有明確時間表', 0),
                    ],
                ],
                [
                    'key'     => 'screen_budget',
                    'title'   => '如果確認這項服務適合你，你目前可考慮投入的預算大約是多少？',
                    'options' => [
                        $o('over_100k', 'NT$100,000 以上', 2),
                        $o('50k_100k', 'NT$50,000–100,000', 2),
                        $o('10k_50k', 'NT$10,000–49,999', 2),
                        $o('6k_10k', 'NT$6,000–9,999', 1),
                        $o('under_6k', 'NT$5,999 以下', 0),
                        $o('none', '目前沒有預算', 0, 'always'),
                        $o('unsure', '不確定，希望先了解方案內容與價格', 1, 'none', ['cap' => 6, 'retired' => true]),
                    ],
                ],
                [
                    'key'     => 'screen_authority',
                    'title'   => '關於這筆投入，你目前的決策狀態最接近哪一種？',
                    'options' => [
                        $o('self', '我可以自行決定，只要確認適合就能開始', 2),
                        $o('discuss', '我是主要決策者，但需要和伴侶／夥伴討論', 2),
                        $o('approval', '需要其他人共同核准', 1),
                        $o('none', '我目前沒有決策權', 0),
                        $o('not_considered', '還沒想過是否要投入', 0),
                    ],
                ],
                [
                    'key'     => 'screen_pain',
                    'title'   => '如果接下來 3–6 個月都沒有改善這個問題，對你的影響有多大？',
                    'options' => [
                        $o('severe', '影響非常大，已經造成明顯損失或壓力', 2),
                        $o('high', '影響很大，希望盡快解決', 2),
                        $o('moderate', '有影響，但目前仍可接受', 1),
                        $o('low', '影響不大', 0),
                        $o('curious', '只是想提前了解', 0),
                    ],
                ],
                [
                    'key'     => 'screen_next_step',
                    'title'   => '如果這次討論後，你認為方向適合，你最可能採取哪個下一步？',
                    'options' => [
                        $o('start_now', '希望盡快確認合作方式並開始', 2),
                        $o('evaluate', '願意認真評估方案與費用後決定', 2),
                        $o('compare', '需要再比較其他選項', 1),
                        $o('diy', '想先自己嘗試一段時間', 0, 'strict'),
                        $o('advice_only', '目前主要是想獲得一些建議，暫時沒有合作打算', 0, 'strict'),
                    ],
                ],
                [
                    'key'     => 'screen_experience',
                    'title'   => '你在經營社群這件事上，認真摸索多久了？',
                    'hint'    => '一對一諮詢最能幫上忙的，是已經動手嘗試過、對社群經營有基本認知的人 —— 這樣我們才能針對你實際卡住的地方討論，而不是從零講起。',
                    'options' => [
                        $o('not_started', '還沒開始', 0),
                        $o('casual', '偶爾發文，沒有特別花心思研究', 0),
                        $o('under_1y', '認真摸索過，累積不到 1 年', 0),
                        $o('1_3y', '認真摸索過，累積 1–3 年', 0),
                        $o('over_3y', '認真摸索過，累積 3 年以上', 0),
                    ],
                ],
                [
                    'key'     => 'screen_learning',
                    'title'   => '針對你現在卡住的問題，你做過最深入的一步是？',
                    'options' => [
                        $o('passive', '平常滑到相關內容會看，但還沒特別針對這個問題去找', 0),
                        $o('searched', '針對這個問題主動搜尋過教學、文章或影片，並試著照做', 0),
                        $o('engaged', '加入相關社群發問交流，或聽過免費講座、直播、體驗課', 0),
                        $o('cheap_paid', '買過專門書籍、講座，或數千元的課程', 0),
                        $o('paid_course', '上過中高價課程或找過顧問', 0),
                    ],
                ],
            ],
            'combos' => [
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
            ],
            'routing' => [
                'chief' => [
                    'enabled'   => true,
                    'min_score' => 8,
                    'question'  => 'screen_budget',
                    'options'   => ['6k_10k', '10k_50k', '50k_100k', 'over_100k'],
                ],
                'others' => [
                    'enabled'   => true,
                    'max_score' => 7,
                    'question'  => 'screen_budget',
                    'options'   => ['under_6k'],
                ],
            ],
        ];
    }
}
