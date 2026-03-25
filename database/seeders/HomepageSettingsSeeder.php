<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use App\Models\SocialLink;
use Illuminate\Database\Seeder;

class HomepageSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'hero_title'          => '經營者時間銀行',
            'hero_description'    => '省去摸索、試錯，高效經營你的人生，朝著健康、快樂、富足前進。',
            'hero_button_label'   => '',
            'hero_button_url'     => '',
            'hero_banner_path'    => null,
            'blog_rss_url'        => 'https://getwhealthy.substack.com/feed',
            'sns_section_enabled' => '1',
        ];

        foreach ($settings as $key => $value) {
            SiteSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        $socialLinks = [
            ['sort_order' => 1, 'platform' => 'instagram', 'url' => 'https://www.instagram.com/kyontw'],
            ['sort_order' => 2, 'platform' => 'threads',   'url' => 'https://www.threads.com/@yueyuknows'],
            ['sort_order' => 3, 'platform' => 'youtube',   'url' => 'https://www.youtube.com/@kyontw828'],
            ['sort_order' => 4, 'platform' => 'facebook',  'url' => 'https://www.facebook.com/kyontw828'],
            ['sort_order' => 5, 'platform' => 'substack',  'url' => 'https://getwhealthy.substack.com/'],
            ['sort_order' => 6, 'platform' => 'podcast',   'url' => 'https://kyontw.firstory.io/'],
        ];

        foreach ($socialLinks as $link) {
            SocialLink::updateOrCreate(
                ['platform' => $link['platform'], 'sort_order' => $link['sort_order']],
                ['url' => $link['url']]
            );
        }
    }
}
