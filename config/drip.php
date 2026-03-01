<?php

return [
    // video_access_hours moved to Lesson.video_access_hours field (per-lesson)

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
