<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\Purchase;
use Illuminate\Database\Eloquent\Collection;

class CartService
{
    public function add(int $userId, int $courseId): ?CartItem
    {
        $existing = CartItem::where('user_id', $userId)->where('course_id', $courseId)->first();
        if ($existing) {
            return $existing;
        }
        return CartItem::create(['user_id' => $userId, 'course_id' => $courseId]);
    }

    public function remove(int $userId, int $courseId): bool
    {
        return CartItem::where('user_id', $userId)->where('course_id', $courseId)->delete() > 0;
    }

    public function getItems(int $userId): Collection
    {
        return CartItem::forUser($userId)->with('course')->get();
    }

    public function count(int $userId): int
    {
        return CartItem::forUser($userId)->count();
    }

    public function mergeGuestCart(int $userId, array $courseIds): void
    {
        foreach ($courseIds as $courseId) {
            $courseId = (int) $courseId;
            if (CartItem::where('user_id', $userId)->where('course_id', $courseId)->exists()) {
                continue;
            }
            if (Purchase::where('user_id', $userId)->where('course_id', $courseId)->where('status', 'paid')->exists()) {
                continue;
            }
            CartItem::create(['user_id' => $userId, 'course_id' => $courseId]);
        }
    }

    public function clearPurchased(int $userId, array $courseIds): void
    {
        CartItem::where('user_id', $userId)->whereIn('course_id', $courseIds)->delete();
    }
}
