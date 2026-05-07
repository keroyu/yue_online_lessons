<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddToCartRequest;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    public function __construct(private CartService $cartService) {}

    public function index(): Response
    {
        $user = Auth::user();

        if (!$user) {
            return Inertia::render('Cart/Index', [
                'items' => [],
                'total' => 0,
            ]);
        }

        $items = $this->cartService->getItems($user->id);
        $mapped = $items->map(fn ($item) => [
            'id'     => $item->id,
            'course' => [
                'id'              => $item->course->id,
                'name'            => $item->course->name,
                'price'           => (float) $item->course->price,
                'thumbnail'       => $item->course->thumbnail_url ?? null,
                'payment_gateway' => $item->course->payment_gateway,
            ],
        ]);

        return Inertia::render('Cart/Index', [
            'items' => $mapped,
            'total' => $mapped->sum(fn ($i) => $i['course']['price']),
        ]);
    }

    public function add(AddToCartRequest $request): JsonResponse
    {
        $userId = Auth::id();
        $courseId = $request->validated('course_id');

        $existing = \App\Models\CartItem::where('user_id', $userId)->where('course_id', $courseId)->exists();
        if ($existing) {
            return response()->json([
                'cartCount' => $this->cartService->count($userId),
                'message'   => '課程已在購物車中',
            ], 409);
        }

        $this->cartService->add($userId, $courseId);

        return response()->json([
            'cartCount' => $this->cartService->count($userId),
        ]);
    }

    public function remove(int $courseId): JsonResponse
    {
        $userId = Auth::id();
        $removed = $this->cartService->remove($userId, $courseId);

        if (!$removed) {
            return response()->json(['message' => '購物車中找不到此課程'], 404);
        }

        return response()->json([
            'cartCount' => $this->cartService->count($userId),
        ]);
    }

    public function merge(Request $request): JsonResponse
    {
        $request->validate([
            'course_ids'   => ['required', 'array'],
            'course_ids.*' => ['integer', 'exists:courses,id'],
        ]);

        $userId = Auth::id();
        $this->cartService->mergeGuestCart($userId, $request->input('course_ids'));

        return response()->json([
            'cartCount' => $this->cartService->count($userId),
        ]);
    }
}
