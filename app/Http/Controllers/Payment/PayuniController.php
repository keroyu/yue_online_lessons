<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\PayuniService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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
        $validated = $request->validate([
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'email'     => ['required', 'email', 'max:255'],
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

        $email      = auth()->user()?->email ?? $validated['email'];
        $merTradeNo = $this->payuniService->generateMerTradeNo($course->id);

        Log::info('PayUni: initiating payment', [
            'course_id'   => $course->id,
            'email'       => $email,
            'MerTradeNo'  => $merTradeNo,
            'amount'      => $course->display_price,
        ]);

        $formData = $this->payuniService->buildPaymentForm($course, $email, $merTradeNo);

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
            $result = $this->payuniService->processNotify($request);
        } catch (\Exception $e) {
            Log::error('PayUni Notify: unexpected exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $result = '1|OK';
        }

        return response($result, 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Handle PayUni ReturnURL — browser redirect after payment.
     * Decrypts result and redirects the user to the appropriate page.
     *
     * POST /api/payment/payuni/return
     */
    public function return(Request $request): RedirectResponse
    {
        $encryptInfo = $request->input('EncryptInfo', '');
        $hashInfo    = $request->input('HashInfo', '');

        Log::info('PayUni Return received', ['has_encrypt' => !empty($encryptInfo)]);

        $data = $this->payuniService->verifyAndDecrypt($encryptInfo, $hashInfo);

        if (!$data) {
            Log::warning('PayUni Return: verification failed');
            return redirect('/')->with('error', '付款驗證失敗，請聯絡客服。');
        }

        $isSuccess   = ($data['Status'] ?? '') === 'SUCCESS' && ($data['TradeStatus'] ?? '') == '1';
        $merTradeNo  = $data['MerTradeNo'] ?? '';
        $courseId    = $this->payuniService->parseCourseId($merTradeNo);

        Log::info('PayUni Return: result', [
            'Status'      => $data['Status'] ?? null,
            'TradeStatus' => $data['TradeStatus'] ?? null,
            'MerTradeNo'  => $merTradeNo,
        ]);

        if ($isSuccess) {
            return redirect('/member/learning')->with('flash', ['payuni_paid' => true]);
        }

        $fallback = $courseId ? "/course/{$courseId}?payment_failed=1" : '/';
        return redirect($fallback);
    }
}
