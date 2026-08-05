<?php

namespace Tests\Feature\HighTicket;

use App\Models\EmailTemplate;
use Database\Seeders\EmailTemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 011 FR-052 — every event_type the code reads must exist in production.
 *
 * Three separate outages came from a template that lived only in the seeder,
 * so the guarantee is worth pinning: install what is missing, never touch what
 * is there.
 */
class EmailTemplateInstallTest extends TestCase
{
    use RefreshDatabase;

    private function runMigration(): void
    {
        (require base_path('database/migrations/2026_08_08_000003_install_missing_email_templates.php'))->up();
    }

    public function test_it_installs_every_canonical_template_into_an_empty_table(): void
    {
        EmailTemplate::query()->delete();

        $this->runMigration();

        foreach (EmailTemplateSeeder::templates() as $template) {
            $this->assertDatabaseHas('email_templates', ['event_type' => $template['event_type']]);
        }
    }

    /** The one the owner reported missing. */
    public function test_the_slot_available_template_is_installed(): void
    {
        EmailTemplate::where('event_type', 'high_ticket_slot_available')->delete();

        $this->runMigration();

        $template = EmailTemplate::forEvent('high_ticket_slot_available')->first();

        $this->assertNotNull($template);
        $this->assertStringContainsString('{{booking_url}}', $template->body_md, '新裝的模板要自帶回訪連結（FR-042）');
    }

    /** An edited template is the owner's copy — repair must not overwrite it. */
    public function test_it_never_touches_an_existing_template(): void
    {
        $edited = EmailTemplate::updateOrCreate(
            ['event_type' => 'high_ticket_slot_available'],
            ['name' => '我改過的', 'event_type' => 'high_ticket_slot_available',
             'subject' => '自訂主旨', 'body_md' => '自訂內容']
        );

        $this->runMigration();

        $edited->refresh();
        $this->assertSame('自訂主旨', $edited->subject);
        $this->assertSame('自訂內容', $edited->body_md);
    }

    public function test_running_it_twice_creates_no_duplicates(): void
    {
        EmailTemplate::query()->delete();

        $this->runMigration();
        $this->runMigration();

        $counts = EmailTemplate::selectRaw('event_type, count(*) c')->groupBy('event_type')->pluck('c');

        $this->assertTrue($counts->every(fn ($c) => $c === 1), 'event_type 沒有 unique 約束，重跑不可產生重複列');
    }
}
