<?php

namespace Database\Seeders;

use App\Models\HomepageWidget;
use App\Models\SiteSetting;
use App\Models\SocialLink;
use App\Services\HomepageWidgetService;
use Illuminate\Database\Seeder;

class HomepageSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'hero_title'           => '經營者時間銀行',
            'hero_subtitle'        => '',
            'hero_description'     => '省去摸索、試錯，高效經營你的人生，朝著健康、快樂、富足前進。',
            'hero_banner_path'     => null,
            // Empty = no 📌 line; pointing it at a course is an editorial
            // decision, not something a fresh install should guess (FR-070).
            'hero_promo_course_id' => '',
        ];

        foreach ($settings as $key => $value) {
            SiteSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        $socialLinks = [
            ['sort_order' => 1, 'platform' => 'instagram', 'url' => 'https://www.instagram.com/kyontw'],
            ['sort_order' => 2, 'platform' => 'threads',   'url' => 'https://www.threads.com/@yueyuknows'],
            ['sort_order' => 3, 'platform' => 'youtube',   'url' => 'https://www.youtube.com/@kyontw828'],
            ['sort_order' => 4, 'platform' => 'facebook',  'url' => 'https://www.facebook.com/kyontw828'],
            ['sort_order' => 5, 'platform' => 'blog',      'url' => 'https://getwhealthy.substack.com/'],
            ['sort_order' => 6, 'platform' => 'podcast',   'url' => 'https://kyontw.firstory.io/'],
        ];

        foreach ($socialLinks as $link) {
            SocialLink::updateOrCreate(
                ['platform' => $link['platform'], 'sort_order' => $link['sort_order']],
                ['url' => $link['url']]
            );
        }

        // Homepage blocks (002 US23). The migration already creates these on
        // any database that has one; this is for a seeder-only rebuild.
        // firstOrCreate, never update: order and visibility belong to the
        // admin the moment the site is live, and re-seeding must not undo them.
        $orderInArea = [];

        foreach (HomepageWidgetService::BUILTIN as $key => $builtin) {
            $area = $builtin['area'];
            $orderInArea[$area] = ($orderInArea[$area] ?? -1) + 1;

            HomepageWidget::firstOrCreate(['key' => $key], [
                'type'       => HomepageWidget::TYPE_BUILTIN,
                'area'       => $area,
                'title'      => $builtin['title'],
                'sort_order' => $orderInArea[$area],
                'is_visible' => true,
            ]);
        }
    }
}
