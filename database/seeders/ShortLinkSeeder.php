<?php

namespace Database\Seeders;

use App\Models\ShortLink;
use Illuminate\Database\Seeder;

class ShortLinkSeeder extends Seeder
{
    /**
     * Idempotent — safe to run on production. Only seeds the link if the slug
     * is absent; the target is managed from /admin/short-links afterwards.
     */
    public function run(): void
    {
        ShortLink::firstOrCreate(
            ['slug' => '1v1'],
            [
                'target_url' => 'https://calendar.app.google/4oQaEE1JbDgSmhhD9',
                'name'       => '1對1諮詢預約',
                'is_active'  => true,
            ],
        );
    }
}
