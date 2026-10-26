<?php

namespace Tests\Feature\Admin;

use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 009 D12 / 008 FR-012 — admin course grants go through member import only.
 * The transactions list no longer creates purchases, and the gift mail
 * template is gone with the gift feature.
 */
class TransactionManualAddRemovedTest extends TestCase
{
    use RefreshDatabase;

    public function test_transactions_can_no_longer_be_created_by_hand(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->postJson('/admin/transactions', ['user_id' => 1, 'course_id' => 1, 'type' => 'gift'])
            ->assertStatus(405);
    }

    public function test_the_course_gifted_template_is_not_seeded(): void
    {
        $this->seed(\Database\Seeders\EmailTemplateSeeder::class);

        $this->assertFalse(EmailTemplate::where('event_type', 'course_gifted')->exists());
    }
}
