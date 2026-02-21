<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Video Free Viewing Window (hours)
    |--------------------------------------------------------------------------
    |
    | How many hours after a drip lesson unlocks the video remains in the
    | "free viewing" period. After this window, the video is still playable
    | but an urgency promo block is shown. Set to null to disable.
    |
    */
    'video_access_hours' => env('DRIP_VIDEO_ACCESS_HOURS', 48),

    /*
    |--------------------------------------------------------------------------
    | 準時到課獎勵等待時間（分鐘）
    |--------------------------------------------------------------------------
    |
    | 會員進入頁面後需連續停留滿此時間才達標獲得獎勵。
    | 計時為 per-session：離開後歸零，下次重新計算。
    | 設為 null 可停用此功能（所有 Lesson 皆不顯示獎勵欄）。
    |
    */
    'reward_delay_minutes' => env('DRIP_REWARD_DELAY_MINUTES', 10),
];
