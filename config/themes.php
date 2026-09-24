<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default scheme
    |--------------------------------------------------------------------------
    |
    | Used when `site_settings.color_scheme` is empty, and as the fallback when
    | it points at a key that no longer exists here (000 FR-119). A scheme may
    | be retired in a future release; the stale value left in the database of a
    | site that had it selected must not take the whole front end down.
    |
    */

    'default' => 'cream-indigo',

    /*
    |--------------------------------------------------------------------------
    | Colour schemes
    |--------------------------------------------------------------------------
    |
    | These live in code rather than in the database (000 D43). Which scheme is
    | active is the operator's choice and belongs in `site_settings`; which
    | schemes exist is a design asset and belongs in git — otherwise changing a
    | swatch means running SQL, tests have to seed before they can assert, and
    | two installs drift apart.
    |
    | The seven keys are ROLES, not hue promises (000 D44): `teal` is burnt
    | sienna in Terracotta and deep berry in Rose Quartz. They keep their
    | original names because renaming them would mean touching 1,099 call sites
    | across 90 files, and a missed one fails silently — that element simply
    | stays the old colour forever.
    |
    |   cream     page canvas (light)
    |   navy      body ink, navbar and admin sidebar (dark, carries white text)
    |   teal      primary action
    |   gold      emphasis CTA fill (carries navy text)
    |   gold_dark that CTA's hover fill and border
    |   orange    secondary emphasis — instalment prices
    |   red       promotion and urgency — sale prices, badges
    |
    | Every scheme here passes the nine contrast gates in 000 FR-121; the gates
    | are an admission requirement, not a suggestion, and `ColorSchemeTest`
    | computes them from these values rather than trusting this comment.
    |
    | All schemes are light-on-dark-ink (000 FR-120). A dark mode would have to
    | swap the `cream` and `navy` roles, which is a different story — not one
    | more entry in this array.
    |
    */

    'schemes' => [

        'cream-indigo' => [
            'name'     => '奶油靛藍',
            'industry' => '知識型／生活風格',
            'colors'   => [
                'cream'     => '#F6F1E9',
                'navy'      => '#373557',
                'teal'      => '#33697F',
                'gold'      => '#F0C14B',
                'gold_dark' => '#C7A33B',
                'orange'    => '#DF6807',
                'red'       => '#D92B1F',
            ],
        ],

        'ink-bamboo' => [
            'name'     => '墨竹',
            'industry' => '東方人文 — 書法、茶道、國學、古典樂',
            'colors'   => [
                'cream'     => '#F5F2EA',
                'navy'      => '#262521',
                'teal'      => '#3F5E56',
                'gold'      => '#C9A96B',
                'gold_dark' => '#B0935A',
                'orange'    => '#BE7C39',
                'red'       => '#B33A2B',
            ],
        ],

        'deep-harbor' => [
            'name'     => '深港',
            'industry' => '商務財經 — 顧問、B2B、投資理財',
            'colors'   => [
                'cream'     => '#F2F5F7',
                'navy'      => '#1B2A38',
                'teal'      => '#2E5F8A',
                'gold'      => '#C9A227',
                'gold_dark' => '#B08F2A',
                'orange'    => '#CA7827',
                'red'       => '#C93B3B',
            ],
        ],

        'moss-field' => [
            'name'     => '苔原',
            'industry' => '健康永續 — 瑜珈、營養、園藝、戶外',
            'colors'   => [
                'cream'     => '#F3F2E8',
                'navy'      => '#262E21',
                'teal'      => '#4A7A3E',
                'gold'      => '#D9A441',
                'gold_dark' => '#BE8F38',
                'orange'    => '#C27A26',
                'red'       => '#BF4332',
            ],
        ],

        'terracotta' => [
            'name'     => '赤陶',
            'industry' => '手作職人 — 烘焙、料理、陶藝、木工',
            'colors'   => [
                'cream'     => '#F7F0E8',
                'navy'      => '#3A2A22',
                'teal'      => '#A34A32',
                'gold'      => '#D4A24C',
                'gold_dark' => '#BB8C3F',
                'orange'    => '#D17031',
                // Deliberately crimson rather than another earth red: `teal` is
                // already burnt sienna here, and a sale badge that reads as the
                // primary action is worse than one that clashes.
                'red'       => '#9B1B3F',
            ],
        ],

        'electric-slate' => [
            'name'     => '電光石板',
            'industry' => '科技數位 — 程式、AI、SaaS、UI 設計',
            'colors'   => [
                'cream'     => '#FAFBFC',
                'navy'      => '#14161C',
                'teal'      => '#2563EB',
                'gold'      => '#D9E84B',
                'gold_dark' => '#A9B82C',
                'orange'    => '#CD7F09',
                'red'       => '#E11D48',
            ],
        ],

        'rose-quartz' => [
            'name'     => '玫瑰石英',
            'industry' => '美感生活 — 美妝、香氛、婚顧、花藝',
            'colors'   => [
                'cream'     => '#FBF3F1',
                'navy'      => '#3A2430',
                'teal'      => '#9B4A68',
                'gold'      => '#E8B07A',
                'gold_dark' => '#BE8652',
                'orange'    => '#D96E48',
                'red'       => '#C02B4E',
            ],
        ],

        'midnight-violet' => [
            'name'     => '夜航紫',
            'industry' => '身心靈創作 — 占星、冥想、音樂、寫作',
            'colors'   => [
                'cream'     => '#F4F1F8',
                'navy'      => '#26203B',
                'teal'      => '#6B4FA8',
                'gold'      => '#D9C08A',
                'gold_dark' => '#AE9662',
                'orange'    => '#CD6C8D',
                'red'       => '#D13D5C',
            ],
        ],

    ],

];
