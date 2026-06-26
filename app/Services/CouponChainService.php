<?php

namespace App\Services;

use App\Models\CouponChain;
use App\Models\CouponCode;
use Illuminate\Support\Facades\Log;

class CouponChainService
{
    /** Characters used when generating a new chain code. */
    private const CHARSET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    /** Length of each auto-generated code. */
    private const CODE_LENGTH = 6;

    /**
     * Replace {alias} placeholders in a promo HTML string with the current
     * active code for each matching chain. Unknown aliases are left as-is.
     */
    public function substitutePlaceholders(?string $html): ?string
    {
        if (!$html || !str_contains($html, '{')) {
            return $html;
        }

        return preg_replace_callback('/\{([a-zA-Z0-9_]+)\}/', function (array $m): string {
            $alias = $m[1];
            $chain = CouponChain::where('alias', $alias)->where('is_active', true)->first();

            if (!$chain) {
                return $m[0]; // unknown alias — leave unchanged
            }

            $code = $chain->currentCode();

            return $code ? $code->code : $m[0]; // no active code yet — leave unchanged
        }, $html);
    }

    /**
     * Generate and persist the next code for a chain.
     * Called automatically by CouponService::redeem() when the current code is exhausted.
     */
    public function generateNextCode(CouponChain $chain): CouponCode
    {
        $code = $this->generateUniqueCode();

        $coupon = CouponCode::create([
            'code'      => $code,
            'type'      => $chain->type,
            'value'     => $chain->value,
            'course_id' => $chain->course_id,
            'max_uses'  => $chain->code_max_uses > 0 ? $chain->code_max_uses : null,
            'is_active' => true,
            'chain_id'  => $chain->id,
        ]);

        Log::info('CouponChainService: new code generated', [
            'chain_alias' => $chain->alias,
            'code'        => $coupon->code,
        ]);

        return $coupon;
    }

    /**
     * Generate a random 6-char uppercase alphanumeric code that does not
     * collide with any existing code (including soft-deleted rows).
     */
    private function generateUniqueCode(): string
    {
        $len = strlen(self::CHARSET);

        do {
            $code = '';
            for ($i = 0; $i < self::CODE_LENGTH; $i++) {
                $code .= self::CHARSET[random_int(0, $len - 1)];
            }
        } while (CouponCode::withTrashed()->where('code', $code)->exists());

        return $code;
    }
}
