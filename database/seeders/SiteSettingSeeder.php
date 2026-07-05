<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class SiteSettingSeeder extends Seeder
{
    /**
     * Seed points-system configurable settings.
     * Uses firstOrCreate so re-seeding never clobbers admin-changed values.
     */
    public function run(): void
    {
        $defaults = [
            'referral_threshold_amount' => '3000', // 推薦啟用累計門檻（元）
            'referral_reward_rate'      => '10',   // 回饋比例（%）
            'homework_reward_points'    => '100',  // 作業完成獎勵點數
            'referral_maturity_days'    => '14',   // 回饋成熟天數 / 含回饋訂單退款期限
        ];

        foreach ($defaults as $key => $value) {
            SiteSetting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
