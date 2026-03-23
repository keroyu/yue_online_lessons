<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\PayuniService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PayuniController extends Controller
{
    public function __construct(
        protected PayuniService $payuniService
    ) {}

    /**
     * Initiate a PayUni UPP payment.
     * Returns JSON form fields for the frontend to auto-submit.
     *
     * POST /api/payment/payuni/initiate
     */
    public function initiate(Request $request): JsonResponse
    {
        $authUser = auth()->user();

        $validated = $request->validate([
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'email'     => ['required', 'email', 'max:255'],
            'name'      => ['required', 'string', 'max:50'],
            'phone'     => ['required', 'string', 'max:20'],
        ]);

        $course = Course::findOrFail($validated['course_id']);

        // Guard: only PayUni-eligible courses
        if ($course->portaly_product_id || $course->price <= 0) {
            return response()->json(['error' => 'This course does not use PayUni'], 422);
        }

        // Guard: draft courses
        if ($course->status === 'draft' || !$course->is_published) {
            return response()->json(['error' => 'Course not available'], 422);
        }

        $email      = $authUser?->email ?? $validated['email'];
        $name       = $validated['name'];
        $phone      = $validated['phone'];
        $merTradeNo = $this->payuniService->generateMerTradeNo($course->id);

        // Store buyer info in cache so NotifyURL callback can look it up
        // (PayUni notify does NOT include email/name in callback payload)
        Cache::put("payuni_order_{$merTradeNo}", [
            'email' => $email,
            'name'  => $name,
            'phone' => $phone,
        ], now()->addHours(2));

        Log::info('PayUni: initiating payment', [
            'course_id'   => $course->id,
            'email'       => $email,
            'MerTradeNo'  => $merTradeNo,
            'amount'      => $course->display_price,
        ]);

        $formData = $this->payuniService->buildPaymentForm($course, $email, $merTradeNo, $name, $phone);

        return response()->json($formData);
    }

    /**
     * Handle PayUni NotifyURL async callback (server-to-server).
     * Always returns 200 to prevent PayUni retry loops.
     *
     * POST /api/webhooks/payuni
     */
    public function notify(Request $request): Response
    {
        Log::info('PayUni Notify received', [
            'MerID'      => $request->input('MerID'),
            'has_encrypt' => !empty($request->input('EncryptInfo')),
        ]);

        try {
            $this->payuniService->processNotify(
                $request->input('EncryptInfo', ''),
                $request->input('HashInfo', '')
            );
        } catch (\Exception $e) {
            Log::error('PayUni Notify: unexpected exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        // Always return '1|OK' to prevent PayUni retry loops
        return response('1|OK', 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Handle PayUni ReturnURL — browser redirect after payment.
     * Also processes purchase creation to prevent race condition with NotifyURL.
     *
     * POST /payment/payuni/return (web route, CSRF excluded)
     */
    public function return(Request $request): RedirectResponse
    {
        $encryptInfo = $request->input('EncryptInfo', '');
        $hashInfo    = $request->input('HashInfo', '');

        Log::info('PayUni Return received', [
            'has_encrypt' => !empty($encryptInfo),
            'auth'        => auth()->check(),
            'all_inputs'  => array_keys($request->all()),
        ]);

        $data = $this->payuniService->verifyAndDecrypt($encryptInfo, $hashInfo);
        $isSuccess = false;
        $courseId = null;

        if ($data) {
            $isSuccess = ($data['Status'] ?? '') === 'SUCCESS' && ($data['TradeStatus'] ?? '') == '1';
            $merTradeNo = $data['MerTradeNo'] ?? '';
            $courseId = $this->payuniService->parseCourseId($merTradeNo);

            Log::info('PayUni Return: result', [
                'Status'      => $data['Status'] ?? null,
                'TradeStatus' => $data['TradeStatus'] ?? null,
                'MerTradeNo'  => $merTradeNo,
            ]);

            // Process payment to handle race condition with NotifyURL (idempotent)
            if ($isSuccess) {
                try {
                    $this->payuniService->processNotify($encryptInfo, $hashInfo);
                } catch (\Exception $e) {
                    Log::error('PayUni Return: processNotify failed', ['error' => $e->getMessage()]);
                }
            }
        } else {
            Log::warning('PayUni Return: verification failed, falling back to redirect');
        }

        // Success path: redirect to learning page or login
        if ($isSuccess) {
            if (auth()->check()) {
                return redirect('/member/learning')->with('success', '付款成功！您的課程已開通。');
            }
            return redirect('/login?hint=payuni');
        }

        // Verification failed but user is logged in — still send to learning page
        // (NotifyURL handles the real payment processing; ReturnURL is just UX)
        if (!$data && auth()->check()) {
            return redirect('/member/learning')->with('success', '付款處理中，課程稍後開通。');
        }

        // Payment failed or unverified guest
        if ($courseId) {
            return redirect("/course/{$courseId}?payment_failed=1");
        }

        // Last resort for guests when verification fails
        return redirect('/login?hint=payuni');
    }
}
