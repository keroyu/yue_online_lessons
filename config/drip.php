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
];
