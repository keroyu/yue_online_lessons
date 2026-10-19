<?php

namespace Tests\Feature\HighTicket;

use App\Models\ConsultationSlot;
use App\Models\Course;
use App\Models\HighTicketLead;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\ConsultationSlotService;
use App\Support\BookingScreening;
use App\Support\ScreeningRubric;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * 011 US46 / FR-265–267 — 1v1 漏斗設定: the screening rubric as editable data.
 */
class FunnelSettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    private function makeCourse(array $overrides = []): Course
    {
        return Course::create(array_merge([
            'name'                   => 'HT Course',
            'slug'                   => 'ht-' . uniqid(),
            'tagline'                => 'tag',
            'description'            => 'desc',
            'price'                  => 58000,
            'instructor_name'        => 'Tester',
            'type'                   => 'high_ticket',
            'status'                 => 'selling',
            'course_type'            => 'standard',
            'is_published'           => true,
            'is_visible'             => true,
            'payment_gateway'        => 'payuni',
            'high_ticket_hide_price' => true,
        ], $overrides))->fresh();
    }

    /** 10/10 under the default rubric, no veto. */
    private function answers(array $overrides = []): array
    {
        return array_merge([
            'screen_timeline'   => 'immediate',
            'screen_budget'     => '10k_50k',
            'screen_authority'  => 'self',
            'screen_pain'       => 'severe',
            'screen_next_step'  => 'start_now',
            'screen_experience' => '1_3y',
            'screen_learning'   => 'paid_course',
        ], $overrides);
    }

    private function screen(Course $course, array $answers, string $email = 'a@example.com')
    {
        return $this->postJson("/course/{$course->id}/screen", array_merge([
            'name'       => 'Applicant',
            'email'      => $email,
            'screen_ack' => true,
        ], $answers));
    }

    /** The stored rubric as the page would post it back. */
    private function payload(?callable $edit = null): array
    {
        $rubric = ScreeningRubric::current();
        unset($rubric['version']);

        return $edit ? $edit($rubric) : $rubric;
    }

    private function save(array $payload)
    {
        return $this->actingAs($this->admin)->put('/admin/funnel-settings', $payload);
    }

    private function questionIndex(array $rubric, string $key): int
    {
        return array_search($key, array_column($rubric['questions'], 'key'), true);
    }

    // ---- defaults ----------------------------------------------------------

    public function test_nothing_saved_means_the_default_rubric(): void
    {
        $this->assertNull(SiteSetting::get(ScreeningRubric::SETTING_KEY));
        $this->assertSame(ScreeningRubric::default(), ScreeningRubric::current());
        $this->assertSame(10, BookingScreening::maxScore());
        $this->assertSame(5, BookingScreening::passScore());
    }

    public function test_saving_the_defaults_unchanged_keeps_every_verdict(): void
    {
        $cases = [
            $this->answers(),
            $this->answers(['screen_budget' => 'none']),
            $this->answers(['screen_next_step' => 'diy']),
            $this->answers(['screen_experience' => 'casual', 'screen_learning' => 'passive', 'screen_budget' => 'under_6k']),
            $this->answers(['screen_timeline' => 'exploring', 'screen_pain' => 'low', 'screen_next_step' => 'compare']),
        ];

        $before = array_map(fn ($a) => [BookingScreening::passes($a), BookingScreening::passes($a, false), BookingScreening::consultantPool($a)], $cases);

        $this->save($this->payload())->assertSessionHasNoErrors();
        $this->assertNotNull(SiteSetting::get(ScreeningRubric::SETTING_KEY));

        $after = array_map(fn ($a) => [BookingScreening::passes($a), BookingScreening::passes($a, false), BookingScreening::consultantPool($a)], $cases);

        $this->assertSame($before, $after);
    }

    // ---- access ------------------------------------------------------------

    public function test_only_admins_reach_the_page(): void
    {
        $this->actingAs($this->admin)->get('/admin/funnel-settings')->assertOk();

        $consultant = User::factory()->create(['is_sales_consultant' => true]);
        // The site's admin middleware sends everybody else home rather than 403.
        $this->actingAs($consultant)->get('/admin/funnel-settings')->assertRedirect('/');
        $this->actingAs($consultant)->put('/admin/funnel-settings', $this->payload())->assertRedirect('/');
        $this->assertNull(SiteSetting::get(ScreeningRubric::SETTING_KEY));
    }

    public function test_the_page_marks_answered_items(): void
    {
        HighTicketLead::create([
            'name' => 'A', 'email' => 'a@example.com', 'course_id' => $this->makeCourse()->id,
            'status' => 'pending', 'booked_at' => now(), 'screened_at' => now(),
            'screen_answers' => ['screen_budget' => 'none'],
        ]);

        $rubric = $this->actingAs($this->admin)->get('/admin/funnel-settings')
            ->assertInertia(fn ($page) => $page->component('Admin/FunnelSettings/Edit'))
            ->viewData('page')['props']['rubric'];

        $budget = $rubric['questions'][$this->questionIndex($rubric, 'screen_budget')];
        $this->assertTrue($budget['answered']);
        $this->assertTrue(collect($budget['options'])->firstWhere('key', 'none')['answered']);
        $this->assertFalse(collect($budget['options'])->firstWhere('key', 'over_100k')['answered']);
        $this->assertFalse($rubric['questions'][$this->questionIndex($rubric, 'screen_pain')]['answered']);
    }

    // ---- questions ---------------------------------------------------------

    public function test_a_new_question_is_shown_required_and_scored(): void
    {
        $this->save($this->payload(function ($r) {
            $r['questions'][] = [
                'key'     => 'screen_newq',
                'title'   => '你目前每週能投入幾小時？',
                'options' => [
                    ['key' => 'lots', 'label' => '10 小時以上', 'score' => 3, 'veto' => 'none'],
                    ['key' => 'few', 'label' => '不到 2 小時', 'score' => 0, 'veto' => 'always'],
                ],
            ];

            return $r;
        }))->assertSessionHasNoErrors();

        $this->assertSame(13, BookingScreening::maxScore());

        $course = $this->makeCourse();
        $fields = collect($this->get("/course/{$course->slug}")->viewData('page')['props']['screeningQuestions'])->pluck('field');
        $this->assertContains('screen_newq', $fields);

        $this->screen($course, $this->answers())->assertStatus(422)->assertJsonValidationErrors(['screen_newq']);

        $this->screen($course, $this->answers(['screen_newq' => 'lots']))->assertOk()->assertJson(['passed' => true]);
        $lead = HighTicketLead::first();
        $this->assertSame(13, $lead->screening_score);
        $this->assertSame('lots', $lead->screen_answers['screen_newq']);

        $this->screen($course, $this->answers(['screen_newq' => 'few']), 'b@example.com')->assertJson(['passed' => false]);
    }

    public function test_server_assigns_keys_to_items_posted_without_one(): void
    {
        $this->save($this->payload(function ($r) {
            $r['questions'][] = [
                'key'     => null,
                'title'   => '新題',
                'options' => [
                    ['key' => null, 'label' => '甲', 'score' => 1, 'veto' => 'none'],
                    ['key' => null, 'label' => '乙', 'score' => 0, 'veto' => 'none'],
                ],
            ];

            return $r;
        }))->assertSessionHasNoErrors();

        $added = collect(ScreeningRubric::current()['questions'])->last();
        $this->assertMatchesRegularExpression('/^screen_[a-z0-9]{6}$/', $added['key']);
        $this->assertCount(2, array_unique(array_column($added['options'], 'key')));
    }

    public function test_removing_an_answered_option_retires_it(): void
    {
        $course = $this->makeCourse();
        $this->screen($course, $this->answers(['screen_pain' => 'moderate']))->assertOk();

        $this->save($this->payload(function ($r) {
            $i = $this->questionIndex($r, 'screen_pain');
            $r['questions'][$i]['options'] = array_values(array_filter(
                $r['questions'][$i]['options'],
                fn ($o) => !in_array($o['key'], ['moderate', 'low'], true),
            ));

            return $r;
        }))->assertSessionHasNoErrors();

        $pain = collect(ScreeningRubric::current()['questions'])->firstWhere('key', 'screen_pain');
        $keys = collect($pain['options'])->pluck('retired', 'key');
        $this->assertTrue($keys['moderate'], '有人選過 → 停用');
        $this->assertArrayNotHasKey('low', $keys->all(), '沒人選過 → 真刪');

        $front = collect(BookingScreening::questionsForFront())->firstWhere('field', 'screen_pain');
        $this->assertNotContains('moderate', array_column($front['options'], 'value'));

        $row = collect(HighTicketLead::first()->screening_answers)->firstWhere('title', $pain['title']);
        $this->assertSame('有影響，但目前仍可接受', $row['answer']);
    }

    public function test_removing_an_answered_question_retires_it_and_stops_asking(): void
    {
        $course = $this->makeCourse();
        $this->screen($course, $this->answers())->assertOk();

        $this->save($this->payload(function ($r) {
            array_splice($r['questions'], $this->questionIndex($r, 'screen_learning'), 1);
            $r['combos'] = [];

            return $r;
        }))->assertSessionHasNoErrors();

        $this->assertNotContains('screen_learning', BookingScreening::fields());
        $this->assertTrue(collect(ScreeningRubric::current()['questions'])->firstWhere('key', 'screen_learning')['retired']);

        $answers = $this->answers();
        unset($answers['screen_learning']);
        $this->screen($course, $answers, 'b@example.com')->assertOk()->assertJson(['passed' => true]);

        $titles = collect(HighTicketLead::first()->screening_answers)->pluck('title');
        $this->assertContains('針對你現在卡住的問題，你做過最深入的一步是？', $titles);
    }

    // ---- rules -------------------------------------------------------------

    public function test_veto_modes_follow_strict_and_lenient(): void
    {
        $this->save($this->payload(function ($r) {
            $i = $this->questionIndex($r, 'screen_authority');
            foreach ($r['questions'][$i]['options'] as &$o) {
                if ($o['key'] === 'discuss') {
                    $o['veto'] = 'strict';
                }
                if ($o['key'] === 'approval') {
                    $o['veto'] = 'always';
                }
            }

            return $r;
        }))->assertSessionHasNoErrors();

        $this->assertFalse(BookingScreening::passes($this->answers(['screen_authority' => 'discuss'])));
        $this->assertTrue(BookingScreening::passes($this->answers(['screen_authority' => 'discuss']), false));
        $this->assertFalse(BookingScreening::passes($this->answers(['screen_authority' => 'approval']), false));
    }

    public function test_a_new_combo_rule_applies(): void
    {
        $answers = $this->answers(['screen_timeline' => '6m_plus', 'screen_pain' => 'low']);
        $this->assertTrue(BookingScreening::passes($answers));

        $this->save($this->payload(function ($r) {
            $r['combos'][] = [
                'match'  => ['screen_timeline' => ['6m_plus', 'exploring'], 'screen_pain' => ['low', 'curious']],
                'unless' => null,
            ];

            return $r;
        }))->assertSessionHasNoErrors();

        $this->assertFalse(BookingScreening::passes($answers));
        $this->assertTrue(BookingScreening::passes($answers, false), '組合否決只在嚴格審核生效');
    }

    public function test_pass_score_and_tiers_are_editable(): void
    {
        $this->save($this->payload(function ($r) {
            $r['pass_score'] = 9;
            $r['tiers'] = ['hot' => 10, 'warm' => 6];

            return $r;
        }))->assertSessionHasNoErrors();

        $this->assertTrue(BookingScreening::passes($this->answers(['screen_pain' => 'moderate'])), '9 分剛好通過');
        $this->assertFalse(BookingScreening::passes($this->answers(['screen_pain' => 'low'])), '8 分不足 9');
        $this->assertSame('warm', BookingScreening::tier(9));
        $this->assertSame('hot', BookingScreening::tier(10));
    }

    // ---- routing -----------------------------------------------------------

    public function test_routing_rules_drive_the_slot_picker(): void
    {
        $consultant = User::factory()->create(['is_sales_consultant' => true]);
        $course = $this->makeCourse();
        $at = Carbon::parse('+1 day 10:00', ConsultationSlotService::DISPLAY_TZ)->utc();
        foreach ([[$this->admin->id, 0], [$consultant->id, 240]] as [$owner, $offset]) {
            for ($i = 0; $i < 2; $i++) {
                ConsultationSlot::create([
                    'starts_at'     => $at->copy()->addMinutes($offset + $i * ConsultationSlot::UNIT_MINUTES),
                    'consultant_id' => $owner,
                ]);
            }
        }

        $labels = fn (array $answers) => collect($this->getJson("/course/{$course->id}/booking-slots?" . http_build_query($answers))->json('slots'))
            ->flatMap(fn ($g) => array_column($g['times'], 'label'))->all();

        // Default: 10/10 + 10k_50k → chief only.
        $this->assertSame(['10:00'], $labels($this->answers()));

        $this->save($this->payload(function ($r) {
            $r['routing']['chief'] = ['enabled' => true, 'min_score' => 8, 'question' => 'screen_authority', 'options' => ['approval']];
            $r['routing']['others'] = ['enabled' => true, 'max_score' => 10, 'question' => 'screen_authority', 'options' => ['self']];

            return $r;
        }))->assertSessionHasNoErrors();

        $this->assertSame(['14:00'], $labels($this->answers()), '改成「決策權 = 自行決定」分給其他顧問');

        $this->save($this->payload(function ($r) {
            $r['routing']['others']['enabled'] = false;

            return $r;
        }))->assertSessionHasNoErrors();

        $this->assertSame(['10:00', '14:00'], $labels($this->answers()));
    }

    public function test_routing_on_a_retired_question_routes_nobody(): void
    {
        $this->makeCourse();
        HighTicketLead::create([
            'name' => 'A', 'email' => 'a@example.com', 'course_id' => Course::first()->id,
            'status' => 'pending', 'booked_at' => now(), 'screened_at' => now(),
            'screen_answers' => ['screen_budget' => '10k_50k'],
        ]);

        $rubric = ScreeningRubric::current();
        $rubric['questions'][$this->questionIndex($rubric, 'screen_budget')]['retired'] = true;
        SiteSetting::set(ScreeningRubric::SETTING_KEY, json_encode($rubric));
        ScreeningRubric::forget();

        $answers = $this->answers();
        unset($answers['screen_budget']);
        $this->assertSame('all', BookingScreening::consultantPool($answers));
    }

    // ---- validation --------------------------------------------------------

    public function test_invalid_rubrics_are_refused(): void
    {
        $original = SiteSetting::get(ScreeningRubric::SETTING_KEY);

        $this->save($this->payload(fn ($r) => array_merge($r, ['pass_score' => 11])))
            ->assertSessionHasErrors('pass_score');

        $this->save($this->payload(fn ($r) => array_merge($r, ['tiers' => ['hot' => 5, 'warm' => 5]])))
            ->assertSessionHasErrors('tiers.hot');

        $this->save($this->payload(function ($r) {
            $r['combos'][] = ['match' => ['screen_nope' => ['x']], 'unless' => null];

            return $r;
        }))->assertSessionHasErrors('combos.1.match');

        $this->save($this->payload(function ($r) {
            $r['routing']['chief']['options'] = ['not_an_option'];

            return $r;
        }))->assertSessionHasErrors('routing.chief.options');

        $this->save($this->payload(function ($r) {
            $i = $this->questionIndex($r, 'screen_pain');
            $r['questions'][$i]['options'] = array_slice($r['questions'][$i]['options'], 0, 1);

            return $r;
        }))->assertSessionHasErrors("questions.{$this->questionIndex(ScreeningRubric::current(), 'screen_pain')}.options");

        $this->save($this->payload(function ($r) {
            $r['questions'][0]['key'] = 'screen_ack';

            return $r;
        }))->assertSessionHasErrors('questions.0.key');

        $this->assertSame($original, SiteSetting::get(ScreeningRubric::SETTING_KEY), '任何錯誤都不寫入');
    }

    // ---- migration ---------------------------------------------------------

    public function test_the_migration_folds_columns_into_json_and_back(): void
    {
        $migration = require database_path('migrations/2026_10_19_000001_move_screening_answers_to_json_on_high_ticket_leads.php');
        $course = $this->makeCourse();

        $migration->down();
        $this->assertTrue(Schema::hasColumn('high_ticket_leads', 'screen_budget'));

        DB::table('high_ticket_leads')->insert([
            ['name' => 'A', 'email' => 'a@example.com', 'course_id' => $course->id, 'status' => 'pending', 'booked_at' => now(),
             'screen_budget' => 'none', 'screen_learning' => 'passive', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'B', 'email' => 'b@example.com', 'course_id' => $course->id, 'status' => 'pending', 'booked_at' => now(),
             'screen_budget' => null, 'screen_learning' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $migration->up();

        $this->assertFalse(Schema::hasColumn('high_ticket_leads', 'screen_budget'));
        $rows = HighTicketLead::orderBy('id')->get();
        $this->assertSame(['screen_budget' => 'none', 'screen_learning' => 'passive'], $rows[0]->screen_answers);
        $this->assertNull($rows[1]->screen_answers);
    }
}
