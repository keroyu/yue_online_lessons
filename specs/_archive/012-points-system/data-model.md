# Data Model: 積分系統擴充 (012-points-system)

**Branch**: `012-points-system` | **Date**: 2026-06-30

---

## Schema Changes Overview

| Change | Table | Type |
|--------|-------|------|
| New table | `point_transactions` | CREATE |
| Add `referral_code`, `referral_activated_at` | `users` | ALTER |
| Add `redeem_points` | `courses` | ALTER |
| Add `referrer_user_id`, `referral_rate`, `referral_reward_points` | `orders` | ALTER |
| New `source` value `'points'` | `purchases` | （無 schema 變更，沿用 `source` 字串欄） |
| 4 組設定鍵 | `site_settings` | seed（無 schema 變更） |

---

## Migrations (in order)

### 1. `2026_06_30_000001_create_point_transactions_table`

```php
Schema::create('point_transactions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->integer('amount');                       // 正=賺取/派發，負=兌換扣點
    $table->enum('type', [
        'earn_homework',    // 作業完成獎勵（即時成熟）
        'redeem_course',    // 兌換課程扣點（負值，即時）
        'earn_referral',    // 推薦回饋（+，14 天成熟）
        'refund_reversal',  // 退款作廢未成熟回饋（對銷）
        'admin_grant',      // 後台派發（正值，即時）
    ]);
    $table->string('reference_type', 30)->nullable(); // 'order' | 'assignment' | 'admin'
    $table->unsignedBigInteger('reference_id')->nullable();
    $table->string('note', 255)->nullable();          // 派發原因 / 備註
    $table->timestamp('available_at');                // 成熟可用時間
    $table->timestamp('created_at');                  // write-once，無 updated_at
    $table->boolean('matured_synced')->default(false);// 成熟後是否已計入 users.points 快取

    $table->index(['user_id', 'created_at']);
    $table->index(['user_id', 'available_at']);
    $table->index(['type', 'available_at', 'matured_synced']); // 成熟結算掃描
    $table->index(['reference_type', 'reference_id']);         // 退款作廢查找
});
```

**Notes**:
- `$timestamps = false`，僅 `created_at` 於 `boot()` 手動賦值（比照 `LessonProgress`、`AssignmentCompletion`）。
- `amount` 用帶號 `integer`：正值賺取/派發、負值扣點。餘額為各筆加總。
- **即時成熟筆**（`earn_homework`/`redeem_course`/`admin_grant`）：`available_at = created_at`，且寫入時同步動 `users.points` 快取，建立後即 `matured_synced=true`。
- **延遲成熟筆**（`earn_referral`）：`available_at = now + maturity_days`，寫入時 `matured_synced=false`、**不**動快取；成熟後由 `points:mature` 排程計入快取並設 `matured_synced=true`。
- `refund_reversal`：作廢尚未成熟的 `earn_referral`。因被作廢筆未成熟、未入快取，對銷筆 `amount` 與被銷筆相反但同樣不影響快取（兩者皆未成熟）。實作上亦可直接刪除原 `earn_referral` 筆（見 R8）；採對銷以保留稽核軌跡。

### 2. `2026_06_30_000002_add_referral_fields_to_users_table`

```php
Schema::table('users', function (Blueprint $table) {
    $table->string('referral_code', 12)->nullable()->unique()->after('points');
    $table->timestamp('referral_activated_at')->nullable()->after('referral_code');
});
```

**Notes**:
- `referral_code` 先 nullable 以利既有資料 alter，隨後由 backfill 補滿；新會員於 `User::booted()` 自動產生。
- backfill（同 migration 內或獨立 seeder）：為既有會員產生唯一碼；並對 `SUM(purchases.amount WHERE type='paid') >= 門檻` 者寫入 `referral_activated_at = now()`。

### 3. `2026_06_30_000003_add_redeem_points_to_courses_table`

```php
Schema::table('courses', function (Blueprint $table) {
    $table->unsignedInteger('redeem_points')->nullable()->after('price');
});
```

**Notes**: `null` 或 `0` = 不可兌換、僅能購買；> 0 = 可用該積分數兌換。不綁定 `type` enum。

### 4. `2026_06_30_000004_add_referral_fields_to_orders_table`

```php
Schema::table('orders', function (Blueprint $table) {
    $table->foreignId('referrer_user_id')->nullable()->after('discount_amount')
          ->constrained('users')->nullOnDelete();
    $table->unsignedTinyInteger('referral_rate')->nullable()->after('referrer_user_id'); // 快照 %
    $table->unsignedInteger('referral_reward_points')->default(0)->after('referral_rate');
    $table->index('referrer_user_id'); // 推薦統計查詢
});
```

**Notes**: 與既有折扣碼欄位（`coupon_code`/`original_amount`/`discount_amount`）並存、互不干擾。

---

## Model Definitions

### `PointTransaction` (`app/Models/PointTransaction.php`) — NEW

```php
public $timestamps = false;

protected $fillable = [
    'user_id', 'amount', 'type', 'reference_type',
    'reference_id', 'note', 'available_at', 'matured_synced',
];

protected function casts(): array {
    return [
        'amount'         => 'integer',
        'available_at'   => 'datetime',
        'created_at'     => 'datetime',
        'matured_synced' => 'boolean',
    ];
}

protected static function boot(): void {
    parent::boot();
    static::creating(function ($model) {
        $model->created_at = $model->freshTimestamp();
    });
}

public function user(): BelongsTo {
    return $this->belongsTo(User::class);
}

public function scopeMatured(Builder $q): Builder {
    return $q->where('available_at', '<=', now());
}

public function scopePending(Builder $q): Builder {
    return $q->where('available_at', '>', now());
}
```

### `User` additions

```php
// $fillable += 'referral_code', 'referral_activated_at'
// casts() += 'referral_activated_at' => 'datetime'

protected static function booted(): void {
    static::creating(function (User $user) {
        if (empty($user->referral_code)) {
            $user->referral_code = static::generateReferralCode();
        }
    });
}

public static function generateReferralCode(): string {
    // 8 碼大寫英數，排除易混字元 0/O/1/I/L；碰撞重試
    do {
        $code = collect(str_split('23456789ABCDEFGHJKMNPQRSTUVWXYZ'))
            ->random(8)->implode('');
    } while (static::where('referral_code', $code)->exists());
    return $code;
}

public function pointTransactions(): HasMany {
    return $this->hasMany(PointTransaction::class)->orderByDesc('created_at');
}

public function isReferralActive(): bool {
    return $this->referral_activated_at !== null;
}

// 可用餘額 = 已成熟快取（users.points）
// 未成熟 = $this->pointTransactions()->pending()->sum('amount')
public function pendingPoints(): int {
    return (int) $this->pointTransactions()->pending()->sum('amount');
}
```

**Note**: `points` 欄位語意由本功能起改為「**已成熟可用餘額**」快取（既有作業發點即時成熟，語意相容）。

### `Course` additions

```php
// $fillable += 'redeem_points'
// casts() += 'redeem_points' => 'integer'

public function isRedeemable(): Attribute {
    return Attribute::get(fn () => $this->redeem_points !== null && $this->redeem_points > 0);
}
```

### `Order` additions

```php
// $fillable += 'referrer_user_id', 'referral_rate', 'referral_reward_points'
// casts() += referral_rate/referral_reward_points => 'integer'

public function referrer(): BelongsTo {
    return $this->belongsTo(User::class, 'referrer_user_id');
}
```

### `Purchase` (沿用)

兌換建立的 Purchase：`source='points'`、`type='paid'`、`amount=0`、`status='paid'`、`order_id=null`（兌換不經訂單）。教室存取依 `status='paid'` 判定，不受影響。

---

## Settings (site_settings) — seed

| Key | Default | 說明 |
|-----|---------|------|
| `referral_threshold_amount` | `3000` | 推薦啟用累計門檻（元） |
| `referral_reward_rate` | `10` | 回饋比例（%） |
| `homework_reward_points` | `100` | 作業完成獎勵點數 |
| `referral_maturity_days` | `14` | 回饋成熟天數（亦為含回饋訂單的退款期限） |

---

## Service Contracts（行為摘要）

### `PointService`（帳本唯一寫入點）

| 方法 | 簽章（概念） | 行為 |
|------|------|------|
| `award` | `(User $u, int $amount, string $type, ?string $refType, ?int $refId, ?string $note, ?Carbon $availableAt = null)` | 寫一筆正值帳本；即時成熟則同步 `increment('points')` 並 `matured_synced=true`；延遲成熟則不動快取。回 `PointTransaction`。 |
| `redeemDeduct` | `(User $u, int $cost, string $refType, int $refId)` | 先 `syncMatured($u)`；再條件式 `UPDATE ... WHERE points >= cost`；0 筆 → `throw`；成功寫一筆負值即時帳本。 |
| `availableBalance` | `(User $u): int` | 先 `syncMatured($u)` 再回 `users.points`，保證「成熟即可用」。 |
| `syncMatured` | `(User $u): void` | 將該會員 `earn_referral` 且 `available_at<=now`、`matured_synced=false` 的筆計入 `users.points` 並標記（單會員即時成熟、冪等）。 |
| `voidReferral` | `(Order $o): void` | 對銷該 order 尚未成熟的 `earn_referral`（冪等）。 |
| `matureDue` | `(): int` | 排程用：將到期未同步的 `earn_referral` 計入快取、標記 `matured_synced`。 |
| `reconcile` | `(): array` | 對帳：找出 `users.points != SUM(已成熟帳本)` 的會員，供 `points:reconcile` 指令／測試斷言用（守雙真相來源）。 |

**唯一寫入點原則**：`users.points` 僅由 `PointService` 在 `DB::transaction` 內寫入；`AssignmentService`、`MemberController`（派發）、`RedemptionService`、`points:mature` 一律經 `PointService`，不得直接 `increment('points')`。

### `ReferralService`

| 方法 | 行為 |
|------|------|
| `validateAtCheckout(string $code, array $buyerIdentity): array` | 正規化碼 → 查 referrer；不存在／自薦／未啟用 → `['success'=>false,'error'=>'…']`；通過回 `['success'=>true,'referrer'=>User,'rate'=>int]`。 |
| `reward(Order $order): void` | 付款後：以 `order.total_amount * rate` 四捨五入到十位，呼叫 `PointService::award(..., 'earn_referral', available_at = now+days)`。 |
| `evaluateActivation(User $user): void` | 重算 `SUM(amount WHERE type='paid')`，≥ 門檻且未啟用則點亮 `referral_activated_at`。 |

### `RedemptionService`

| 方法 | 行為 |
|------|------|
| `redeem(User $user, Course $course): array` | `DB::transaction`：檢查可兌換、未擁有 → `PointService::redeemDeduct` → 建 `source='points'` Purchase → 回 `['success'=>true,'purchase'=>…]`；失敗回結構化錯誤。 |

---

## Entity Relationship Summary

```
User
├── hasMany → PointTransaction        (帳本，所有積分異動)
├── hasMany → Purchase                (含 source='points' 兌換取得)
├── referral_code (unique, 永久)
└── referral_activated_at (單向旗標)

Course
└── redeem_points (nullable; null/0 = 不可兌換)

Order
├── belongsTo → User (referrer, FK referrer_user_id, nullable)
├── referral_rate / referral_reward_points (快照)
└── (既存) coupon_code / discount_amount  ← 與推薦並存

PointTransaction
└── belongsTo → User
    reference_type/id → order | assignment | admin（無 FK，快照式關聯）
```

---

## Indexes（彙總）

| Table | Index | Reason |
|-------|-------|--------|
| `point_transactions` | `(user_id, created_at)` | 會員/後台帳本明細列表 |
| `point_transactions` | `(user_id, available_at)` | 未成熟餘額查詢 |
| `point_transactions` | `(type, available_at, matured_synced)` | 成熟結算排程掃描 |
| `point_transactions` | `(reference_type, reference_id)` | 退款作廢查找 |
| `users` | `referral_code` UNIQUE | 結帳推薦碼比對 + 永久唯一 |
| `orders` | `referrer_user_id` | 推薦成效統計 |
