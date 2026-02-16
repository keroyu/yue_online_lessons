# Research: Email 連鎖加溫系統

**Feature**: 005-drip-email
**Date**: 2026-02-05

## Research Tasks

### 1. Laravel Scheduler Best Practices

**Decision**: 使用 Laravel 原生 Scheduler + Queue 系統

**Rationale**:
- 專案已在 `routes/console.php` 配置排程（已有 `courses:update-status` 指令）
- Laravel Forge 已支援 cron 執行 `schedule:run`
- 使用 database queue driver（已配置在 `config/queue.php`）

**Alternatives Considered**:
- Supervisor + dedicated queue worker: 過於複雜，不符合 "Simplicity Over Complexity" 原則
- 外部排程服務（如 AWS EventBridge）: 增加基礎設施複雜度

**Implementation**:
```php
// routes/console.php
Schedule::command('drip:process-emails')->dailyAt('09:00');
```

---

### 2. Email 發送策略

**Decision**: 使用現有 Resend.com 整合 + Laravel Queue

**Rationale**:
- 專案已有 `app/Mail/` 下的 Mailable 類別模式
- 已配置 Resend driver 在 `config/mail.php`
- 使用 `ShouldQueue` 確保不阻塞排程執行

**Implementation Pattern**:
```php
// 參考現有 CourseGiftedMail.php 模式
class DripLessonMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
}
```

**Retry Strategy**:
- 最多 3 次重試（`$tries = 3`）
- 指數退避（`$backoff = [60, 300, 900]`）

---

### 3. 訂閱者身份處理（訪客 vs 會員）

**Decision**: 複用現有 VerificationCodeService 進行 Email 驗證

**Rationale**:
- 專案已有完整的驗證碼流程（`app/Services/VerificationCodeService.php`）
- 統一會員管理，不另建訂閱者表

**Flow**:
1. 訪客輸入 Email → 發送驗證碼
2. 驗證成功 → 檢查 User 是否存在
3. 不存在 → 建立 User（僅 email）
4. 存在 → 使用現有 User
5. 建立 DripSubscription 關聯

---

### 4. Portaly Webhook 整合

**Decision**: 擴充現有 `PortalyWebhookService`

**Rationale**:
- 專案已有完整的 webhook 處理邏輯
- 只需在 `handlePaidEvent` 後加入轉換檢測

**Implementation**:
```php
// 在 PortalyWebhookService::handlePaidEvent 後
$this->dripService->checkAndConvert($user, $course);
```

---

### 5. 解鎖邏輯計算

**Decision**: 純計算方式，不儲存解鎖狀態

**Rationale**:
- 解鎖日公式：`sort_order × drip_interval_days`（sort_order 從 0 開始）
- 每次請求時計算，不需要額外資料表
- 符合 "Simplicity Over Complexity" 原則

**Implementation**:
```php
public function isLessonUnlocked(DripSubscription $subscription, Lesson $lesson): bool
{
    $daysSinceSubscription = $subscription->subscribed_at->diffInDays(now());
    $unlockDay = $lesson->sort_order * $subscription->course->drip_interval_days;
    return $daysSinceSubscription >= $unlockDay;
}
```

---

### 6. 退訂 Token 安全性

**Decision**: 使用 UUID v4 作為 unsubscribe_token

**Rationale**:
- 64 位元熵，足夠防止猜測
- Laravel 內建 `Str::uuid()`
- 不需要額外加密

**Implementation**:
```php
$subscription->unsubscribe_token = Str::uuid()->toString();
```

---

### 7. 前端狀態管理

**Decision**: 使用 Inertia.js props 傳遞狀態，不需額外狀態管理

**Rationale**:
- 符合專案現有模式
- 訂閱狀態在頁面載入時由後端計算並傳入
- 訂閱操作後重新載入頁面（Inertia reload）

---

### 8. 影片免費觀看期限（Video Access Window）

**Decision**: Config 檔案儲存 + 後端計算 + 前端倒數

**Rationale**:
- 全站統一設定，不需要 per-course 彈性 → config 最簡單
- 後端計算過期時間並傳給前端（`video_access_expires_at`），避免前端自行計算造成時間不一致
- 前端只負責倒數顯示和過期 UI 切換
- 不需要新增 DB 欄位或 migration

**Config Structure**:
```php
// config/drip.php
return [
    'video_access_hours' => env('DRIP_VIDEO_ACCESS_HOURS', 48),
];
```

**Calculation**:
```php
// DripService
public function getVideoAccessExpiresAt(DripSubscription $subscription, Lesson $lesson): ?Carbon
{
    $unlockDay = $lesson->sort_order * $subscription->course->drip_interval_days;
    $unlockAt = $subscription->subscribed_at->copy()->addDays($unlockDay);
    $hours = config('drip.video_access_hours');
    return $unlockAt->addHours($hours);
}
```

**Urgency Promo Content**: 系統自動生成（非自訂 HTML），包含：
- 固定文案：「免費觀看期已結束，但我們為你保留了存取權。想要完整學習體驗？」
- 動態內容：目標課程名稱和連結（從 DripConversionTarget 讀取）
- 無目標課程時：通用文案 + 課程列表連結

**Alternatives Considered**:
- DB 欄位 per-course：過度設計，目前不需要 per-course 彈性
- 前端純計算：時間計算依賴 subscription 和 lesson 資料，後端計算更可靠
- 完全鎖定影片（方案 B）：風險過高，可能造成負面觀感

---

## Technology Decisions Summary

| Area | Decision | Key Reason |
|------|----------|------------|
| Scheduler | Laravel native + Queue | 已有配置，簡單 |
| Email | Resend + ShouldQueue | 複用現有整合 |
| Auth | VerificationCodeService | 統一驗證流程 |
| Webhook | 擴充 PortalyWebhookService | 最小變更 |
| Unlock Logic | 純計算，不儲存 | 簡單、無狀態 |
| Token | UUID v4 | 安全、簡單 |
| Frontend | Inertia props | 符合現有模式 |
| Video Access Window | Config + 後端計算 | 簡單、全站統一 |

## No Unresolved Clarifications

All technical decisions have been made based on existing project patterns and constitution principles.
