<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\DripService;

class PortalyWebhookService
{
    /**
     * Verify the webhook signature using HMAC-SHA256
     */
    public function verifySignature(Request $request): bool
    {
        $signature = $request->header('X-Portaly-Signature');
        $secret = config('services.portaly.webhook_secret');

        if (!$signature || !$secret) {
            Log::warning('Webhook: Missing signature or secret', [
                'has_signature' => !empty($signature),
                'has_secret' => !empty($secret),
            ]);
            return false;
        }

        // Use JSON_UNESCAPED flags to match Portaly's JSON encoding
        $data = json_encode($request->input('data'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $expectedSignature = hash_hmac('sha256', $data, $secret);

        $isValid = hash_equals($expectedSignature, $signature);

        if (!$isValid) {
            Log::warning('Webhook: Signature mismatch', [
                'received' => $signature,
                'expected' => $expectedSignature,
            ]);
        }

        return $isValid;
    }

    /**
     * Parse webhook payload and return structured data
     */
    public function parsePayload(Request $request): array
    {
        return [
            'event' => $request->input('event'),
            'timestamp' => $request->input('timestamp'),
            'data' => $request->input('data', []),
        ];
    }

    /**
     * Get or create user by email
     * If user doesn't exist, create with name and phone from customerData
     * If user exists but missing name/phone, update with Portaly data
     */
    public function getOrCreateUser(array $customerData): User
    {
        $email = $customerData['email'] ?? null;

        if (!$email) {
            throw new \InvalidArgumentException('Customer email is required');
        }

        $user = User::where('email', $email)->first();

        if ($user) {
            // Update name and phone if currently empty and Portaly provides them
            $updates = [];

            if (empty($user->real_name) && !empty($customerData['name'])) {
                $updates['real_name'] = $customerData['name'];
            }

            if (empty($user->phone) && !empty($customerData['phone'])) {
                $updates['phone'] = $customerData['phone'];
            }

            if (!empty($updates)) {
                $user->update($updates);
                Log::info('Webhook: Updated existing user with Portaly data', [
                    'user_id' => $user->id,
                    'updates' => $updates,
                ]);
            }

            return $user;
        }

        // Create new user with data from Portaly
        return User::create([
            'email' => $email,
            'real_name' => $customerData['name'] ?? null,
            'phone' => $customerData['phone'] ?? null,
            'role' => 'member',
        ]);
    }

    /**
     * Create purchase record from webhook data
     * Returns null if purchase already exists (idempotency)
     */
    public function createPurchase(User $user, array $data): ?Purchase
    {
        $portalyOrderId = $data['id'] ?? null;

        if (!$portalyOrderId) {
            throw new \InvalidArgumentException('Portaly order ID is required');
        }

        // Check idempotency - if already processed, return null
        $existingPurchase = Purchase::where('portaly_order_id', $portalyOrderId)->first();
        if ($existingPurchase) {
            Log::info('Webhook: Duplicate order received', ['portaly_order_id' => $portalyOrderId]);
            return null;
        }

        // Find course by Portaly product ID
        $productId = $data['productId'] ?? null;
        $course = Course::where('portaly_product_id', $productId)->first();

        if (!$course) {
            // Product not associated with any course - silently ignore
            // This happens when Portaly sends webhooks for unrelated products
            Log::info('Webhook: Ignoring unrelated product', ['productId' => $productId]);
            return null;
        }

        // Create purchase record
        return Purchase::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'portaly_order_id' => $portalyOrderId,
            'buyer_email' => $data['customerData']['email'] ?? $user->email,
            'amount' => $data['amount'] ?? 0,
            'currency' => $data['currency'] ?? 'TWD',
            'coupon_code' => !empty($data['couponCode']) ? $data['couponCode'] : null,
            'discount_amount' => $data['discount'] ?? 0,
            'status' => 'paid',
            'type' => 'paid',
            'webhook_received_at' => now(),
        ]);
    }

    /**
     * Handle refund event - update purchase status to refunded
     */
    public function handleRefund(array $data): bool
    {
        $portalyOrderId = $data['id'] ?? null;

        if (!$portalyOrderId) {
            Log::error('Webhook: Refund event missing order ID');
            return false;
        }

        $purchase = Purchase::where('portaly_order_id', $portalyOrderId)->first();

        if (!$purchase) {
            Log::warning('Webhook: Refund for non-existent order', ['portaly_order_id' => $portalyOrderId]);
            return false;
        }

        $purchase->update([
            'status' => 'refunded',
        ]);

        Log::info('Webhook: Purchase refunded', ['portaly_order_id' => $portalyOrderId]);
        return true;
    }

    /**
     * Process the webhook based on event type
     */
    public function process(Request $request): array
    {
        $payload = $this->parsePayload($request);
        $event = $payload['event'];
        $data = $payload['data'];

        switch ($event) {
            case 'paid':
                return $this->handlePaidEvent($data);

            case 'refund':
                $success = $this->handleRefund($data);
                return [
                    'success' => true,
                    'message' => $success ? 'Refund processed' : 'Order not found for refund',
                ];

            default:
                Log::warning('Webhook: Unknown event type', ['event' => $event]);
                return [
                    'success' => true,
                    'message' => 'Unknown event type ignored',
                ];
        }
    }

    /**
     * Handle paid event
     */
    protected function handlePaidEvent(array $data): array
    {
        $customerData = $data['customerData'] ?? [];
        $productId = $data['productId'] ?? null;

        // Check if this product is associated with a course first
        // to avoid creating users for unrelated Portaly products
        $course = Course::where('portaly_product_id', $productId)->first();

        if (!$course) {
            // Silently ignore unrelated products - no need to log every time
            return [
                'success' => true,
                'message' => 'Unrelated product, ignored',
            ];
        }

        Log::info('Webhook: Processing paid event', [
            'order_id' => $data['id'] ?? 'unknown',
            'email' => $customerData['email'] ?? 'unknown',
            'product_id' => $productId,
            'course_id' => $course->id,
        ]);

        try {
            $user = $this->getOrCreateUser($customerData);
            Log::info('Webhook: User resolved', [
                'user_id' => $user->id,
                'email' => $user->email,
                'is_new' => $user->wasRecentlyCreated,
            ]);

            $purchase = $this->createPurchase($user, $data);

            if ($purchase) {
                Log::info('Webhook: Purchase created', [
                    'purchase_id' => $purchase->id,
                    'user_id' => $user->id,
                    'portaly_order_id' => $data['id'],
                ]);

                $dripService = app(DripService::class);

                // Auto-subscribe if purchased course is a drip course
                if ($purchase->course->course_type === 'drip') {
                    $result = $dripService->subscribe($user, $purchase->course);
                    Log::info('Webhook: Drip subscription attempt', [
                        'success' => $result['success'],
                        'error' => $result['error'] ?? null,
                    ]);
                }

                // Check if purchase triggers drip conversion
                $dripService->checkAndConvert($user, $purchase->course);

                return [
                    'success' => true,
                    'message' => 'Purchase created',
                    'purchase_id' => $purchase->id,
                ];
            }

            Log::info('Webhook: Duplicate order skipped', ['order_id' => $data['id']]);
            return [
                'success' => true,
                'message' => 'Duplicate order, skipped',
            ];
        } catch (\Exception $e) {
            Log::error('Webhook: Unexpected error in handlePaidEvent', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $data,
            ]);
            throw $e;
        }
    }
}
