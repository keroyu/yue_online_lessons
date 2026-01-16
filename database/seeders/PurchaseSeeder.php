<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CourseProgress;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Database\Seeder;

class PurchaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $member1 = User::where('email', 'member1@example.com')->first();
        $member2 = User::where('email', 'member2@example.com')->first();

        $course1 = Course::where('portaly_product_id', 'prod_001')->first();
        $course2 = Course::where('portaly_product_id', 'prod_002')->first();
        $course3 = Course::where('portaly_product_id', 'prod_003')->first();

        // Member 1 purchased course 1 and 2
        Purchase::create([
            'user_id' => $member1->id,
            'course_id' => $course1->id,
            'portaly_order_id' => 'ORD_001',
            'amount' => $course1->price,
            'currency' => 'TWD',
            'status' => 'paid',
        ]);

        CourseProgress::create([
            'user_id' => $member1->id,
            'course_id' => $course1->id,
            'progress_percent' => 0,
        ]);

        Purchase::create([
            'user_id' => $member1->id,
            'course_id' => $course2->id,
            'portaly_order_id' => 'ORD_002',
            'amount' => $course2->price,
            'currency' => 'TWD',
            'status' => 'paid',
        ]);

        CourseProgress::create([
            'user_id' => $member1->id,
            'course_id' => $course2->id,
            'progress_percent' => 0,
        ]);

        // Member 2 purchased course 3
        Purchase::create([
            'user_id' => $member2->id,
            'course_id' => $course3->id,
            'portaly_order_id' => 'ORD_003',
            'amount' => $course3->price,
            'currency' => 'TWD',
            'coupon_code' => 'WELCOME10',
            'discount_amount' => 499,
            'status' => 'paid',
        ]);

        CourseProgress::create([
            'user_id' => $member2->id,
            'course_id' => $course3->id,
            'progress_percent' => 0,
        ]);
    }
}
