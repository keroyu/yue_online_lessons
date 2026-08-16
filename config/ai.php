<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default model
    |--------------------------------------------------------------------------
    |
    | Last resort in the resolution chain (000 FR-029):
    |   ai_prompts.model → site_settings.openai_default_model → this value.
    |
    */

    'default_model' => env('OPENAI_DEFAULT_MODEL', 'gpt-5.6-luna'),

    /*
    |--------------------------------------------------------------------------
    | Selectable models
    |--------------------------------------------------------------------------
    |
    | Drives the per-prompt model dropdown in the admin panel. Kept here rather
    | than hardcoded in the Vue page so a new OpenAI release is a one-line
    | change (000 US10 acceptance).
    |
    */

    'models' => [
        'gpt-5.6-luna'  => 'GPT-5.6 Luna（成本最低，適合校訂等機械性工作）',
        'gpt-5.6-sol'   => 'GPT-5.6 Sol',
        'gpt-5.6-terra' => 'GPT-5.6 Terra',
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature groups
    |--------------------------------------------------------------------------
    |
    | `ai_prompts.feature` → the heading the admin page groups prompts under.
    | Adding an AI feature means adding a row here and a row in `ai_prompts`;
    | the settings page renders whatever it finds.
    |
    */

    'features' => [
        'consultation' => '面談整理',
    ],

    /*
    |--------------------------------------------------------------------------
    | Request defaults
    |--------------------------------------------------------------------------
    */

    'endpoint'          => 'https://api.openai.com/v1/responses',
    'timeout'           => 120,
    'retry_times'       => 2,
    'retry_sleep_ms'    => 2000,
    'max_output_tokens' => 4000,

];
