<?php

namespace Tests\Feature\HighTicket;

use App\Models\Course;
use App\Models\EmailTemplate;
use App\Models\User;
use App\Services\HighTicketBookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Support\BooksHighTicket;
use Tests\TestCase;

/**
 * 011 US7 — a template can hold raw HTML instead of Markdown (FR-019), every
 * template mail carries a plain-text part (FR-020), and a single Enter in the
 * Markdown editor is a real line break (FR-021).
 */
class EmailTemplateHtmlModeTest extends TestCase
{
    use BooksHighTicket, RefreshDatabase;

    private function template(string $bodyType, string $body): EmailTemplate
    {
        return EmailTemplate::updateOrCreate(['event_type' => 'high_ticket_booking_confirmation'], [
            'name'       => '預約確認信',
            'event_type' => 'high_ticket_booking_confirmation',
            'subject'    => '{{course_name}} 預約確認',
            'body_type'  => $bodyType,
            'body_md'    => $body,
        ]);
    }

    /** @see BookingMailFailureTest — sqlite's CHECK predates the 'high_ticket' type. */
    private function makeHighTicketCourse(): Course
    {
        $course = Course::create([
            'name'                   => 'HT Course',
            'slug'                   => 'ht-' . uniqid(),
            'tagline'                => 'tag',
            'description'            => 'desc',
            'price'                  => 50000,
            'instructor_name'        => 'Tester',
            'type'                   => 'lecture',
            'status'                 => 'selling',
            'course_type'            => 'standard',
            'is_published'           => true,
            'is_visible'             => true,
            'payment_gateway'        => 'payuni',
            'high_ticket_hide_price' => true,
        ]);
        $course->type = 'high_ticket';

        return $course;
    }

    public function test_html_mode_is_delivered_verbatim(): void
    {
        $html = '<div style="color: #ff1f1f; font-weight: 700;">Step. 1</div>';

        $rendered = $this->template('html', $html)->renderBody([]);

        $this->assertSame($html, $rendered, 'HTML 模式不得經過 CommonMark 或任何後處理');
    }

    public function test_markdown_mode_still_converts_to_html(): void
    {
        $rendered = $this->template('markdown', '# 標題')->renderBody([]);

        $this->assertStringContainsString('<h1>標題</h1>', $rendered);
    }

    public function test_body_type_defaults_to_markdown(): void
    {
        $template = EmailTemplate::updateOrCreate(['event_type' => 'course_gifted'], [
            'name'       => '舊模板',
            'event_type' => 'course_gifted',
            'subject'    => 's',
            'body_md'    => '**粗體**',
        ]);

        $this->assertSame('markdown', $template->fresh()->body_type);
        $this->assertStringContainsString('<strong>粗體</strong>', $template->fresh()->renderBody([]));
    }

    public function test_a_single_newline_is_a_real_line_break(): void
    {
        // FR-021 — pressing Enter once used to be swallowed, so the admin had
        // to press it twice to get any break at all.
        $rendered = $this->template('markdown', "第一行\n第二行")->renderBody([]);

        $this->assertStringContainsString('<br />', $rendered);
        $this->assertSame(1, substr_count($rendered, '<p>'), '單次換行應留在同一段，不該變成兩段');
    }

    public function test_a_blank_line_is_still_a_new_paragraph(): void
    {
        $rendered = $this->template('markdown', "第一段\n\n第二段")->renderBody([]);

        $this->assertSame(2, substr_count($rendered, '<p>'));
        $this->assertStringNotContainsString('<br />', $rendered);
    }

    public function test_variables_are_replaced_in_both_modes_including_html_attributes(): void
    {
        $vars = ['{{user_name}}' => '小明', '{{course_name}}' => '諮詢'];

        $html = $this->template('html', '<a href="https://x.test/?n={{user_name}}">{{course_name}}</a>')
            ->renderBody($vars);

        $this->assertSame('<a href="https://x.test/?n=小明">諮詢</a>', $html);
    }

    public function test_render_text_returns_the_markdown_source_untouched(): void
    {
        $md = "您好 {{user_name}}\n\n感謝您的預約。";

        $text = $this->template('markdown', $md)->renderText(['{{user_name}}' => '小明']);

        $this->assertSame("您好 小明\n\n感謝您的預約。", $text);
    }

    public function test_render_text_flattens_html_and_keeps_the_urls(): void
    {
        $text = $this->template('html', implode('', [
            '<div>您好</div>',
            '<div><a href="https://youtu.be/abc">https://youtu.be/abc</a></div>',
            '<div><a href="https://x.test/book">預約時段</a></div>',
            '<div>Q &amp; A</div>',
        ]))->renderText([]);

        $this->assertStringContainsString('您好', $text);
        $this->assertStringContainsString('Q & A', $text, 'HTML entity 應還原成純文字');
        $this->assertStringNotContainsString('<', $text, '純文字段不該留下任何標籤');
        // Anchor text that already is the URL must not be printed twice.
        $this->assertSame(1, substr_count($text, 'https://youtu.be/abc'));
        // Anchor text that is not the URL loses the link unless we keep it.
        $this->assertStringContainsString('預約時段 (https://x.test/book)', $text);
    }

    public function test_booking_confirmation_carries_the_raw_html_and_a_text_part(): void
    {
        Mail::fake();
        $this->template('html', '<div style="color:#ff1f1f">請看完影片</div>');

        $this->applyAndConfirm($this->makeHighTicketCourse());

        Mail::assertSent(\App\Mail\TemplatedMail::class, function ($mail) {
            return $mail->htmlBody === '<div style="color:#ff1f1f">請看完影片</div>'
                && str_contains($mail->textBody, '請看完影片');
        });
    }

    public function test_the_mail_actually_renders_both_an_html_and_a_text_part(): void
    {
        // FR-020 — HTML-only mail is a spam-filter penalty of its own.
        $mail = new \App\Mail\TemplatedMail('主旨', '<div>圖文版</div>', '純文字版');

        $mail->assertSeeInHtml('<div>圖文版</div>', false);
        $mail->assertSeeInText('純文字版');
        $mail->assertDontSeeInText('<div>');
    }

    public function test_admin_can_switch_a_template_to_html_mode(): void
    {
        $admin = User::create(['email' => 'admin@example.com', 'role' => 'admin']);
        $template = $this->template('markdown', '舊內容');

        $this->actingAs($admin)
            ->put("/admin/email-templates/{$template->id}", [
                'name'      => '預約確認信',
                'subject'   => '主旨',
                'body_type' => 'html',
                'body_md'   => '<div>新內容</div>',
            ])
            ->assertRedirect(route('admin.email-templates.index'));

        $this->assertSame('html', $template->fresh()->body_type);
    }

    public function test_admin_cannot_save_an_unknown_body_type(): void
    {
        $admin = User::create(['email' => 'admin@example.com', 'role' => 'admin']);
        $template = $this->template('markdown', '舊內容');

        $this->actingAs($admin)
            ->put("/admin/email-templates/{$template->id}", [
                'name'      => '預約確認信',
                'subject'   => '主旨',
                'body_type' => 'blade',
                'body_md'   => 'x',
            ])
            ->assertSessionHasErrors('body_type');

        $this->assertSame('markdown', $template->fresh()->body_type);
    }
}
