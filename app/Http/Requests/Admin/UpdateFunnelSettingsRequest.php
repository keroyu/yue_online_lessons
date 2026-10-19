<?php

namespace App\Http\Requests\Admin;

use App\Support\ScreeningRubric;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * The whole screening rubric from 1v1 漏斗設定 (011 US46 / FR-267).
 *
 * Field shapes are plain rules; everything that is a relation between parts —
 * a rule pointing at a question that exists, a pass mark the questions can
 * reach — is checked in after(), against the rubric as submitted rather than
 * the stored one, because the page edits both at once.
 */
class UpdateFunnelSettingsRequest extends FormRequest
{
    public const QUESTION_KEY = '/^screen_[a-z0-9_]{1,24}$/';

    public const OPTION_KEY = '/^[a-z0-9_]{1,24}$/';

    /** The admin middleware handles authorization. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pass_score'                        => ['required', 'integer', 'min:0', 'max:200'],
            'tiers.hot'                         => ['required', 'integer', 'min:0', 'max:200'],
            'tiers.warm'                        => ['required', 'integer', 'min:0', 'max:200'],

            'questions'                         => ['required', 'array', 'min:1', 'max:40'],
            'questions.*.key'                   => ['nullable', 'string', 'regex:' . self::QUESTION_KEY, Rule::notIn(['screen_ack'])],
            'questions.*.title'                 => ['required', 'string', 'max:200'],
            'questions.*.hint'                  => ['nullable', 'string', 'max:500'],
            'questions.*.retired'               => ['sometimes', 'boolean'],
            'questions.*.options'               => ['required', 'array', 'min:1', 'max:30'],
            'questions.*.options.*.key'         => ['nullable', 'string', 'regex:' . self::OPTION_KEY],
            'questions.*.options.*.label'       => ['required', 'string', 'max:100'],
            'questions.*.options.*.score'       => ['required', 'integer', 'min:0', 'max:10'],
            'questions.*.options.*.veto'        => ['required', Rule::in(ScreeningRubric::VETO_MODES)],
            'questions.*.options.*.cap'         => ['nullable', 'integer', 'min:0', 'max:200'],
            'questions.*.options.*.retired'     => ['sometimes', 'boolean'],

            'combos'                            => ['present', 'array', 'max:20'],
            'combos.*.match'                    => ['required', 'array', 'min:1'],
            'combos.*.unless'                   => ['nullable', 'array'],
            'combos.*.unless.match'             => ['required_with:combos.*.unless', 'array', 'min:1'],
            'combos.*.unless.min_score'         => ['required_with:combos.*.unless', 'integer', 'min:0', 'max:200'],

            'routing.chief.enabled'             => ['required', 'boolean'],
            'routing.chief.min_score'           => ['required', 'integer', 'min:0', 'max:200'],
            'routing.chief.question'            => ['nullable', 'string'],
            'routing.chief.options'             => ['present', 'array'],
            'routing.others.enabled'            => ['required', 'boolean'],
            'routing.others.max_score'          => ['required', 'integer', 'min:0', 'max:200'],
            'routing.others.question'           => ['nullable', 'string'],
            'routing.others.options'            => ['present', 'array'],
        ];
    }

    public function attributes(): array
    {
        return [
            'questions.*.title'           => '題目',
            'questions.*.options.*.label' => '選項文字',
            'questions.*.options.*.score' => '分數',
            'pass_score'                  => '通過分數',
        ];
    }

    public function after(): array
    {
        return [fn (Validator $validator) => $this->checkRelations($validator)];
    }

    private function checkRelations(Validator $validator): void
    {
        if ($validator->errors()->isNotEmpty()) {
            return;
        }

        $rubric = $this->rubric($validator->getData());
        $active = [];
        $questionKeys = [];

        foreach ($rubric['questions'] as $i => $question) {
            if ($question['key'] !== null) {
                if (in_array($question['key'], $questionKeys, true)) {
                    $validator->errors()->add("questions.{$i}.key", '題目代號重複');
                }
                $questionKeys[] = $question['key'];
            }

            $optionKeys = array_filter(array_column($question['options'], 'key'));
            if (count($optionKeys) !== count(array_unique($optionKeys))) {
                $validator->errors()->add("questions.{$i}.options", '選項代號重複');
            }

            if ($question['retired']) {
                continue;
            }

            $live = array_filter($question['options'], fn ($o) => !$o['retired']);

            if (count($live) < 2) {
                $validator->errors()->add("questions.{$i}.options", '每題至少需要 2 個啟用中的選項');
            }

            if (count($live) > 12) {
                $validator->errors()->add("questions.{$i}.options", '每題最多 12 個啟用中的選項');
            }

            if ($question['key'] !== null) {
                $active[$question['key']] = array_column($live, 'key');
            }
        }

        $liveQuestions = count(array_filter($rubric['questions'], fn ($q) => !$q['retired']));
        if ($liveQuestions < 1 || $liveQuestions > 20) {
            $validator->errors()->add('questions', '啟用中的題目需介於 1 到 20 題');
        }

        $max = ScreeningRubric::maxScore($rubric);

        if ($rubric['pass_score'] > $max) {
            $validator->errors()->add('pass_score', "通過分數不可高於滿分 {$max}");
        }

        if ($rubric['tiers']['hot'] <= $rubric['tiers']['warm']) {
            $validator->errors()->add('tiers.hot', '「高購買意願」門檻必須高於「值得談」');
        }

        foreach ($rubric['combos'] as $i => $combo) {
            $this->checkMatch($validator, "combos.{$i}.match", $combo['match'], $active);

            if ($combo['unless'] !== null) {
                $this->checkMatch($validator, "combos.{$i}.unless.match", $combo['unless']['match'], $active);
            }
        }

        foreach (['chief', 'others'] as $pool) {
            $rule = $rubric['routing'][$pool];

            if (!$rule['enabled']) {
                continue;
            }

            if (!isset($active[$rule['question']])) {
                $validator->errors()->add("routing.{$pool}.question", '請選擇一個啟用中的題目');

                continue;
            }

            if ($rule['options'] === [] || array_diff($rule['options'], $active[$rule['question']]) !== []) {
                $validator->errors()->add("routing.{$pool}.options", '請至少勾選一個該題的啟用中選項');
            }
        }
    }

    /** @param array<string, array<int, string>> $active */
    private function checkMatch(Validator $validator, string $path, array $match, array $active): void
    {
        foreach ($match as $question => $options) {
            if (!isset($active[$question])) {
                $validator->errors()->add($path, '規則引用的題目不存在或已停用');

                return;
            }

            if (!is_array($options) || $options === [] || array_diff($options, $active[$question]) !== []) {
                $validator->errors()->add($path, '規則中每個題目至少勾選一個啟用中的選項');

                return;
            }
        }
    }

    /**
     * The submitted rubric reduced to the stored shape: known keys only,
     * types cast, empty optionals dropped.
     */
    public function rubric(?array $data = null): array
    {
        // Raw input inside after(), where validated() would re-run validation;
        // by then the field rules have passed, so the shapes are known.
        $data ??= $this->validated();

        $questions = array_map(fn ($q) => array_filter([
            'key'     => $q['key'] ?? null,
            'title'   => trim($q['title']),
            'hint'    => filled($q['hint'] ?? null) ? trim($q['hint']) : null,
            'retired' => (bool) ($q['retired'] ?? false),
            'options' => array_map(fn ($o) => array_filter([
                'key'     => $o['key'] ?? null,
                'label'   => trim($o['label']),
                'score'   => (int) $o['score'],
                'veto'    => $o['veto'],
                'cap'     => isset($o['cap']) ? (int) $o['cap'] : null,
                'retired' => (bool) ($o['retired'] ?? false),
            ], fn ($v, $k) => $v !== null || $k === 'key', ARRAY_FILTER_USE_BOTH), array_values($q['options'])),
        ], fn ($v, $k) => $v !== null || $k === 'key', ARRAY_FILTER_USE_BOTH), array_values($data['questions']));

        // Normalised so the relation checks can read every flag without `??`.
        foreach ($questions as &$question) {
            $question['retired'] ??= false;
            foreach ($question['options'] as &$option) {
                $option['retired'] ??= false;
            }
            unset($option);
        }
        unset($question);

        $match = fn ($m) => array_map(fn ($values) => array_values((array) $values), (array) $m);

        return [
            'pass_score' => (int) $data['pass_score'],
            'tiers'      => ['hot' => (int) $data['tiers']['hot'], 'warm' => (int) $data['tiers']['warm']],
            'questions'  => $questions,
            'combos'     => array_map(fn ($c) => [
                'match'  => $match($c['match']),
                'unless' => isset($c['unless']['match'])
                    ? ['match' => $match($c['unless']['match']), 'min_score' => (int) $c['unless']['min_score']]
                    : null,
            ], array_values($data['combos'] ?? [])),
            'routing' => [
                'chief' => [
                    'enabled'   => (bool) $data['routing']['chief']['enabled'],
                    'min_score' => (int) $data['routing']['chief']['min_score'],
                    'question'  => $data['routing']['chief']['question'] ?? null,
                    'options'   => array_values($data['routing']['chief']['options'] ?? []),
                ],
                'others' => [
                    'enabled'   => (bool) $data['routing']['others']['enabled'],
                    'max_score' => (int) $data['routing']['others']['max_score'],
                    'question'  => $data['routing']['others']['question'] ?? null,
                    'options'   => array_values($data['routing']['others']['options'] ?? []),
                ],
            ],
        ];
    }
}
