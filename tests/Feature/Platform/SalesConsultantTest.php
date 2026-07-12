<?php

namespace Tests\Feature\Platform;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesConsultantTest extends TestCase
{
    use RefreshDatabase;

    private function consultant(): User
    {
        return User::factory()->create(['role' => 'member', 'is_sales_consultant' => true]);
    }

    // --- 000 US6: restricted staff access ---

    public function test_consultant_can_access_coupons_and_leads(): void
    {
        $user = $this->consultant();

        $this->actingAs($user)->get('/admin/coupons')->assertOk();
        $this->actingAs($user)->get('/admin/high-ticket-leads')->assertOk();
    }

    public function test_consultant_is_blocked_from_other_admin_pages(): void
    {
        $user = $this->consultant();

        $this->actingAs($user)->get('/admin')->assertRedirect('/');
        $this->actingAs($user)->get('/admin/members')->assertRedirect('/');
        $this->actingAs($user)->get('/admin/transactions')->assertRedirect('/');
        $this->actingAs($user)->get('/admin/settings/payment')->assertRedirect('/');
    }

    public function test_plain_member_and_guest_are_blocked_from_staff_pages(): void
    {
        $member = User::factory()->create(['role' => 'member']);

        $this->actingAs($member)->get('/admin/coupons')->assertRedirect('/');
        // Spec: unauthenticated visitors are also sent home (with the flash error).
        $this->get('/admin/coupons')->assertRedirect('/');
    }

    public function test_admin_retains_full_access(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/admin/coupons')->assertOk();
        $this->actingAs($admin)->get('/admin/members')->assertOk();
    }

    public function test_shared_auth_user_exposes_consultant_flag(): void
    {
        $user = $this->consultant();

        $this->actingAs($user)->get('/admin/coupons')
            ->assertInertia(fn ($page) => $page->where('auth.user.is_sales_consultant', true));
    }

    // --- 008 US9: assigning the consultant flag ---

    public function test_admin_can_toggle_sales_consultant(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['role' => 'member']);

        $this->actingAs($admin)
            ->patchJson("/admin/members/{$member->id}/sales-consultant", ['is_sales_consultant' => true])
            ->assertOk()
            ->assertJson(['is_sales_consultant' => true]);
        $this->assertTrue($member->fresh()->is_sales_consultant);

        $this->actingAs($admin)
            ->patchJson("/admin/members/{$member->id}/sales-consultant", ['is_sales_consultant' => false])
            ->assertOk();
        $this->assertFalse($member->fresh()->is_sales_consultant);
    }

    public function test_toggle_is_admin_only_and_guarded(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $editor = User::factory()->create(['role' => 'editor']);
        $admin = User::factory()->create(['role' => 'admin']);

        // Consultant cannot self-escalate: route sits in the admin-only group.
        $this->actingAs($this->consultant())
            ->patch("/admin/members/{$member->id}/sales-consultant", ['is_sales_consultant' => true])
            ->assertRedirect('/');

        // Editor accounts are outside the manageable-member scope.
        $this->actingAs($admin)
            ->patchJson("/admin/members/{$editor->id}/sales-consultant", ['is_sales_consultant' => true])
            ->assertStatus(403);
    }

    public function test_consultants_endpoint_lists_and_searches(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $consultant = User::factory()->create([
            'role' => 'member', 'is_sales_consultant' => true, 'email' => 'wang@example.com',
        ]);
        User::factory()->create(['role' => 'member', 'email' => 'chen@example.com']);

        // List current consultants; no search → empty results.
        $res = $this->actingAs($admin)->getJson('/admin/members/sales-consultants');
        $res->assertOk()
            ->assertJsonCount(1, 'consultants')
            ->assertJsonPath('consultants.0.email', 'wang@example.com')
            ->assertJsonCount(0, 'results');

        // Search returns unassigned members only.
        $res = $this->actingAs($admin)->getJson('/admin/members/sales-consultants?search=example.com');
        $emails = collect($res->json('results'))->pluck('email');
        $this->assertTrue($emails->contains('chen@example.com'));
        $this->assertFalse($emails->contains('wang@example.com'));

        // Consultant themselves cannot reach the management endpoint (admin-only).
        $this->actingAs($consultant)->get('/admin/members/sales-consultants')->assertRedirect('/');
    }

    public function test_member_detail_returns_consultant_flag(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $member = User::factory()->create(['role' => 'member', 'is_sales_consultant' => true]);

        $this->actingAs($admin)
            ->getJson("/admin/members/{$member->id}")
            ->assertOk()
            ->assertJsonPath('member.is_sales_consultant', true);
    }
}
