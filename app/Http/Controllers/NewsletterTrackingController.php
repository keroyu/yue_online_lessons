<?php

namespace App\Http\Controllers;

use App\Models\NewsletterEmailEvent;
use App\Models\User;
use App\Services\NewsletterService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class NewsletterTrackingController extends Controller
{
    // 1x1 transparent GIF binary
    private const PIXEL = "\x47\x49\x46\x38\x39\x61\x01\x00\x01\x00\x80\x00\x00\xff\xff\xff\x00\x00\x00\x21\xf9\x04\x01\x00\x00\x00\x00\x2c\x00\x00\x00\x00\x01\x00\x01\x00\x00\x02\x02\x44\x01\x00\x3b";

    public function __construct(private NewsletterService $newsletterService) {}

    public function open(Request $request): Response
    {
        if ($request->hasValidSignature()) {
            $broadcastId = $request->integer('broadcast');
            $userId = $request->integer('user');

            try {
                NewsletterEmailEvent::firstOrCreate(
                    [
                        'broadcast_id' => $broadcastId,
                        'user_id' => $userId,
                        'event_type' => 'opened',
                    ],
                    [
                        'ip' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                    ]
                );

                if ($user = User::find($userId)) {
                    $this->newsletterService->recordOpen($user);
                }
            } catch (\Throwable $e) {
                Log::warning('Newsletter open tracking failed', [
                    'broadcast' => $broadcastId,
                    'user' => $userId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response(self::PIXEL, 200, [
            'Content-Type' => 'image/gif',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }
}
