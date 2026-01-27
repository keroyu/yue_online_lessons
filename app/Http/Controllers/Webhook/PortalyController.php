<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Services\PortalyWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PortalyController extends Controller
{
    public function __construct(
        protected PortalyWebhookService $webhookService
    ) {}

    /**
     * Handle Portaly webhook
     */
    public function handle(Request $request): JsonResponse
    {
        // Log incoming webhook for debugging
        Log::info('Portaly webhook received', [
            'event' => $request->input('event'),
            'timestamp' => $request->input('timestamp'),
        ]);

        // Verify signature
        if (!$this->webhookService->verifySignature($request)) {
            Log::warning('Portaly webhook: Invalid signature');
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        try {
            $result = $this->webhookService->process($request);

            return response()->json($result, 200);
        } catch (\Exception $e) {
            // Log error but return 200 to prevent Portaly retry loops
            Log::error('Portaly webhook processing error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Error logged, no retry needed',
            ], 200);
        }
    }
}
