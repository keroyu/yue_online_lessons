<?php

namespace Tests\Feature\Points;

use App\Models\Course;
use App\Models\Purchase;
use App\Models\User;
use App\Services\PointService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RedeemCourseTest extends TestCase
{
    use RefreshDatabase;

    private function makeCourse(?int $redeemPoints = 1000): Course
    {
        return Course::create([
            'name'            => 'Redeemable Lecture',
            'slug'            => 'redeem-' . uniqid(),
            'tagline'         => 'tag',
            'description'     => 'desc',
            'price'           => 2000,
            'redeem_points'   => $redeemPoints,
            'instructor_name' => 'Tester',
            'type'            => 'lecture',
            'status'          => 'selling',
            'course_type'     => 'standard',
            'is_published'    => true,
            'is_visible'      => true,
            'payment_gateway' => 'payuni',
        ]);
    }

    private function userWithPoints(int $points): User
    {
        $user = User::factory()->create();
        if ($points > 0) {
            app(PointService::class)->award($user, $points, 'admin_grant', 'admin', null, 'seed');
        }

        return $user->fresh();
    }

    public function test_redeem_grants_ownership_deducts_points_and_writes_ledger(): void
    {
        $course = $this->makeCourse(1000);
        $user = $this->userWithPoints(1200);

        $this->actingAs($user)
            ->post("/courses/{$course->id}/redeem")
            ->assertRedirect();

        // Ownership via source=points purchase
        $this->assertDatabaseHas('purchases', [
            'user_id'   => $user->id,
            'course_id' => $course->id,
            'source'    => 'points',
            'amount'    => 0,
            'status'    => 'paid',
        ]);

        // Points deducted + ledger row
        $this->assertSame(200, (int) $user->fresh()->points);
        $this->assertDatabaseHas('point_transactions', [
            'user_id' => $user->id,
            'type'    => 'redeem_course',
            'amount'  => -1000,
        ]);
    }

    public function test_insufficient_points_blocks_redeem(): void
    {
        $course = $this->makeCourse(1000);
        $user = $this->userWithPoints(800);

        $this->actingAs($user)
            ->post("/courses/{$course->id}/redeem")
            ->assertSessionHasErrors('redeem');

        $this->assertDatabaseMissing('purchases', [
            'user_id'   => $user->id,
            'course_id' => $course->id,
        ]);
        $this->assertSame(800, (int) $user->fresh()->points);
    }

    public function test_already_owned_course_is_not_redeemed_again(): void
    {
        $course = $this->makeCourse(1000);
        $user = $this->userWithPoints(1200);

        Purchase::create([
            'user_id'    => $user->id,
            'course_id'  => $course->id,
            'amount'     => 0,
            'currency'   => 'TWD',
            'status'     => 'paid',
            'type'       => 'paid',
            'source'     => 'gift',
        ]);

        $this->actingAs($user)
            ->post("/courses/{$course->id}/redeem")
            ->assertSessionHasErrors('redeem');

        // No deduction happened
        $this->assertSame(1200, (int) $user->fresh()->points);
    }

    public function test_non_redeemable_course_is_blocked(): void
    {
        $course = $this->makeCourse(null); // redeem_points null → not redeemable
        $user = $this->userWithPoints(5000);

        $this->actingAs($user)
            ->post("/courses/{$course->id}/redeem")
            ->assertSessionHasErrors('redeem');

        $this->assertSame(5000, (int) $user->fresh()->points);
    }
}
