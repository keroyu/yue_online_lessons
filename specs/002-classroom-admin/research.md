# Research: 上課頁面與管理員後臺

**Branch**: `002-classroom-admin` | **Date**: 2026-01-17
**Updated**: 2026-01-30 - 新增 Course Visibility Toggle 設計決策

## Video Embedding (Vimeo/YouTube)

### Decision
使用官方 iframe embed 方式嵌入影片，透過 URL 解析服務提取 video ID。

### Rationale
- Vimeo 和 YouTube 都提供穩定的 oEmbed API 和 iframe embed
- 不需要額外 SDK，減少依賴
- 符合 Constitution 的 Vimeo embed 要求

### Implementation
```php
// VideoEmbedService.php
class VideoEmbedService
{
    public function parse(string $url): ?array
    {
        // Vimeo: https://vimeo.com/1032766965
        if (preg_match('/vimeo\.com\/(\d+)/', $url, $matches)) {
            return [
                'platform' => 'vimeo',
                'video_id' => $matches[1],
                'embed_url' => "https://player.vimeo.com/video/{$matches[1]}"
            ];
        }

        // YouTube: https://www.youtube.com/watch?v=xxx or https://youtu.be/xxx
        if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return [
                'platform' => 'youtube',
                'video_id' => $matches[1],
                'embed_url' => "https://www.youtube.com/embed/{$matches[1]}"
            ];
        }

        return null;
    }
}
```

### Alternatives Considered
- **Vimeo PHP SDK**: 過度複雜，只需要 embed URL
- **YouTube Data API**: 需要 API key，只用於 embed 不划算

---

## Drag-and-Drop Sorting

### Decision
使用 Vue Draggable (SortableJS) 實作章節拖曳排序。

### Rationale
- SortableJS 是成熟穩定的拖曳排序庫
- vue.draggable.next 提供 Vue 3 支援
- 輕量且無額外依賴

### Implementation
```bash
npm install vuedraggable@next
```

```vue
<draggable v-model="chapters" item-key="id" @end="updateOrder">
  <template #item="{ element }">
    <ChapterItem :chapter="element" />
  </template>
</draggable>
```

### Alternatives Considered
- **原生 HTML5 Drag API**: 需要大量樣板代碼
- **dnd-kit**: React 生態系，不適用 Vue

---

## Image Upload & Storage

### Decision
使用 Laravel 內建 Storage facade，儲存至 `storage/app/public/course-images/`。

### Rationale
- Laravel Storage 提供統一 API，未來可輕鬆切換至 S3
- 符合 Constitution 的簡單優先原則
- 使用 symbolic link 提供公開存取

### Implementation
```bash
php artisan storage:link
```

```php
// 上傳
$path = $request->file('image')->store("course-images/{$courseId}", 'public');

// 刪除
Storage::disk('public')->delete($path);
```

### Constraints
- 單檔最大 10MB (php.ini: upload_max_filesize, post_max_size)
- 允許格式: jpg, jpeg, png, gif, webp

### Alternatives Considered
- **S3 直接上傳**: MVP 階段過度複雜
- **Cloudinary**: 增加外部依賴

---

## Course Status Auto-Switch (預購→熱賣)

### Decision
使用 Laravel Task Scheduling，每分鐘執行檢查。

### Rationale
- Laravel Scheduler 是內建功能，無需額外設定
- 每分鐘檢查足夠即時（符合 SC-007: 1 分鐘內切換）
- 簡單 SQL 查詢，效能影響極小

### Implementation
```php
// app/Console/Kernel.php
$schedule->command('courses:update-status')->everyMinute();

// app/Console/Commands/UpdateCourseStatus.php
Course::where('status', 'preorder')
    ->where('sale_at', '<=', now())
    ->update(['status' => 'selling']);
```

### Alternatives Considered
- **Queue Job with delay**: 複雜度高，需要精確計算延遲時間
- **Event-driven**: 過度設計

---

## Admin Middleware

### Decision
使用自定義 Middleware 檢查 `role === 'admin'`。

### Rationale
- 簡單直接，符合現有 User model 的 role 欄位設計
- 可複用於所有 admin 路由群組

### Implementation
```php
// app/Http/Middleware/AdminMiddleware.php
public function handle($request, Closure $next)
{
    if (!auth()->check() || auth()->user()->role !== 'admin') {
        return redirect('/')->with('error', '您沒有權限存取此頁面');
    }
    return $next($request);
}

// routes/web.php
Route::prefix('admin')->middleware(['auth', 'admin'])->group(function () {
    // admin routes
});
```

### Alternatives Considered
- **Spatie Laravel Permission**: MVP 階段角色簡單，不需要複雜權限套件
- **Gate/Policy only**: 需要在每個 controller 重複檢查

---

## HTML Content Sanitization

### Decision
管理員輸入的 HTML 不做 sanitization，直接儲存和渲染。

### Rationale
- 只有 admin 可以輸入 HTML
- Admin 需要完整 HTML 功能（含 iframe、script 等）來嵌入外部內容
- Constitution 已限制 admin 數量 < 10 人

### Security Note
- 前台顯示時使用 `v-html`，信任 admin 輸入
- 如未來需要開放給非 admin，需重新評估

### Alternatives Considered
- **HTMLPurifier**: 會移除需要的標籤（如 iframe）
- **Markdown only**: 功能受限

---

---

## Promotional Price Countdown Timer (2026-01-17 新增，2026-01-18 更新)

### Decision
使用純前端 Vue 3 計算實作倒數計時，每秒更新一次，製造緊迫感。價格區塊顯示在課程販售頁講師/時間長度右側。

### Rationale
- 每秒更新的倒數計時（HH:MM:SS 格式）製造購買緊迫感，提升轉換率
- 純前端實作避免伺服器負載
- Vue 3 Composition API 的 `computed` + `setInterval` 簡潔實用
- `tabular-nums` CSS 類別避免數字變動時的版面跳動

### Implementation
```vue
<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  promoEndsAt: String,  // ISO 8601 format
  price: Number,
  originalPrice: Number
})

const now = ref(new Date())
let timer = null

onMounted(() => {
  timer = setInterval(() => {
    now.value = new Date()
  }, 1000) // Update every second for urgency effect
})

onUnmounted(() => {
  clearInterval(timer)
})

const isPromoActive = computed(() => {
  if (!props.originalPrice || !props.promoEndsAt) return false
  return new Date(props.promoEndsAt) > now.value
})

const countdown = computed(() => {
  if (!isPromoActive.value) return null
  const diff = new Date(props.promoEndsAt) - now.value
  const days = Math.floor(diff / (1000 * 60 * 60 * 24))
  const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60))
  const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60))
  const seconds = Math.floor((diff % (1000 * 60)) / 1000)
  return { days, hours, minutes, seconds }
})

const displayPrice = computed(() => {
  return isPromoActive.value ? props.price : (props.originalPrice ?? props.price)
})
</script>
```

### Display Logic
- **優惠期間**: 原價（刪除線）+ 優惠價（醒目大字）+ 倒數計時（優惠剩餘 X 天 HH:MM:SS）
- **優惠到期後**: 僅顯示原價（無刪除線）
- **無優惠設定**: 僅顯示優惠價（price）

### Price Block Position
- 位置：課程販售頁講師名稱和時間長度的右側
- 樣式：漸層背景（indigo-purple）、圓角邊框、醒目顯示

### Alternatives Considered
- **Server-side countdown**: 增加伺服器負載且需要 WebSocket 或頻繁輪詢
- **每分鐘更新**: 缺乏緊迫感，無法有效促進購買決策

---

## Image Gallery Modal (2026-01-17 新增)

### Decision
使用 Vue 3 Modal 組件實作同頁相簿選擇器，支援圖片瀏覽、上傳、刪除、尺寸設定。

### Rationale
- 同頁 Modal 避免離開編輯頁面，提升編輯效率
- Vue 3 Teleport 可將 Modal 渲染至 body，避免 z-index 問題
- 符合 SC-009（30 秒內完成插入流程）

### Implementation
```vue
<!-- ImageGalleryModal.vue -->
<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  courseId: Number,
  images: Array,
  show: Boolean
})

const emit = defineEmits(['close', 'insert'])

const selectedImage = ref(null)
const customWidth = ref('')
const customHeight = ref('')

const selectImage = (image) => {
  selectedImage.value = image
  customWidth.value = ''
  customHeight.value = ''
}

const calculateHeight = () => {
  if (customWidth.value && selectedImage.value) {
    const ratio = selectedImage.value.height / selectedImage.value.width
    customHeight.value = Math.round(customWidth.value * ratio)
  }
}

const calculateWidth = () => {
  if (customHeight.value && selectedImage.value) {
    const ratio = selectedImage.value.width / selectedImage.value.height
    customWidth.value = Math.round(customHeight.value * ratio)
  }
}

const insertImage = () => {
  const img = selectedImage.value
  let html = `<img src="${img.url}" alt="${img.filename}"`
  if (customWidth.value) html += ` width="${customWidth.value}"`
  if (customHeight.value) html += ` height="${customHeight.value}"`
  html += ' />'
  emit('insert', html)
  emit('close')
}

const uploadImage = async (event) => {
  const file = event.target.files[0]
  if (!file) return

  const formData = new FormData()
  formData.append('image', file)

  router.post(`/admin/courses/${props.courseId}/images`, formData, {
    preserveScroll: true,
    onSuccess: () => {
      // Image list will be refreshed via Inertia
    }
  })
}
</script>
```

### Upload Enhancement
上傳時自動偵測圖片寬高：
```php
// CourseImageController@store
$image = $request->file('image');
$dimensions = getimagesize($image->getPathname());

CourseImage::create([
    'course_id' => $course->id,
    'path' => $path,
    'filename' => $image->getClientOriginalName(),
    'width' => $dimensions[0] ?? null,
    'height' => $dimensions[1] ?? null,
]);
```

### Alternatives Considered
- **獨立相簿頁面**: 需要離開編輯頁，打斷工作流程
- **Rich Text Editor 內建上傳**: 複雜度高，且無法複用既有相簿

---

## Legal Policy Modal (2026-01-17 新增)

### Decision
使用 Vue 3 Modal 組件實作法律政策頁面，內容為靜態 HTML 直接寫在前端元件中。

### Rationale
- 法律政策內容變更頻率低，不需要後台管理介面
- 靜態內容避免額外 API 請求，開啟速度 < 0.5 秒（符合 SC-011）
- 使用 Vue 3 Teleport 渲染至 body，確保 z-index 正確
- 符合 Constitution 的簡單優先原則

### Implementation
```vue
<!-- LegalPolicyModal.vue -->
<script setup>
import { onMounted, onUnmounted } from 'vue'

const props = defineProps({
  show: Boolean,
  type: String  // 'terms' | 'purchase' | 'privacy'
})

const emit = defineEmits(['close'])

// ESC key handler
const handleEsc = (e) => {
  if (e.key === 'Escape' && props.show) {
    emit('close')
  }
}

onMounted(() => {
  document.addEventListener('keydown', handleEsc)
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleEsc)
})

const titles = {
  terms: '服務條款',
  purchase: '購買須知',
  privacy: '隱私政策'
}
</script>

<template>
  <Teleport to="body">
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
      <!-- Backdrop -->
      <div
        class="fixed inset-0 bg-black/50"
        @click="emit('close')"
      />
      <!-- Modal -->
      <div class="relative min-h-screen flex items-center justify-center p-4">
        <div class="relative bg-white rounded-lg max-w-2xl w-full max-h-[80vh] overflow-y-auto">
          <div class="sticky top-0 bg-white border-b px-6 py-4 flex justify-between">
            <h2 class="text-xl font-bold">{{ titles[type] }}</h2>
            <button @click="emit('close')" class="text-gray-500 hover:text-gray-700">
              <span class="sr-only">關閉</span>
              ✕
            </button>
          </div>
          <div class="px-6 py-4">
            <!-- Terms content -->
            <div v-if="type === 'terms'">
              <h3>服務條款內容...</h3>
              <!-- Static HTML content -->
            </div>

            <!-- Purchase policy content with refund policy -->
            <div v-if="type === 'purchase'">
              <h3 class="font-bold mb-4">退款政策</h3>
              <ul class="list-disc pl-5 space-y-2">
                <li>「迷你課」和「講座」類型課程恕不退款。</li>
                <li>大型課（「課程」類型）的退款申請需在購買後 14 日內提出。</li>
                <li>課程完成度超過 20% 恕不退款。</li>
              </ul>
              <!-- More content -->
            </div>

            <!-- Privacy policy content -->
            <div v-if="type === 'privacy'">
              <h3>隱私政策內容...</h3>
              <!-- Static HTML content -->
            </div>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>
```

### Footer Integration
```vue
<!-- Footer.vue -->
<template>
  <footer class="bg-gray-100 py-8">
    <div class="container mx-auto text-center text-sm text-gray-600">
      <button @click="openModal('terms')" class="hover:underline">服務條款</button>
      <span class="mx-2">|</span>
      <button @click="openModal('purchase')" class="hover:underline">購買須知</button>
      <span class="mx-2">|</span>
      <button @click="openModal('privacy')" class="hover:underline">隱私政策</button>
    </div>
  </footer>

  <LegalPolicyModal
    :show="showModal"
    :type="modalType"
    @close="showModal = false"
  />
</template>
```

### Body Scroll Lock
當 Modal 開啟時鎖定背景滾動：
```javascript
watch(() => props.show, (newVal) => {
  if (newVal) {
    document.body.style.overflow = 'hidden'
  } else {
    document.body.style.overflow = ''
  }
})
```

### Alternatives Considered
- **獨立頁面路由**: 需要離開當前頁面，使用者體驗較差
- **後台管理 CMS**: 內容變更頻率低，過度設計
- **Markdown 檔案**: 需要額外解析步驟，增加複雜度

---

## Lesson Completion Throttling (2026-01-18 新增)

### Decision
使用前端節流機制，會員點擊小節後前端立即顯示完成標記（樂觀更新），但實際完成紀錄需等待停留滿 5 分鐘後才寫入伺服器。

### Rationale
- 避免會員頻繁點選章節時產生過多伺服器請求
- 確保只有真正閱讀/觀看過內容的小節才會被標記為完成
- 前端樂觀更新提供即時視覺回饋，提升用戶體驗
- 純前端計時器實作，伺服器不參與計時邏輯

### Implementation
```vue
<!-- Classroom.vue -->
<script setup>
import { ref, watch, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  course: Object,
  currentLesson: Object
})

// Track completion timers per lesson
const completionTimers = ref({})

// Local optimistic completion state
const localCompletedLessons = ref(new Set())

// Timer threshold: 5 minutes in milliseconds
const COMPLETION_THRESHOLD_MS = 5 * 60 * 1000

const selectLesson = (lesson) => {
  // Cancel any existing timer for previous lesson
  Object.keys(completionTimers.value).forEach(lessonId => {
    if (lessonId !== String(lesson.id)) {
      clearTimeout(completionTimers.value[lessonId])
      delete completionTimers.value[lessonId]
    }
  })

  // Show optimistic completion (green checkmark)
  if (!lesson.is_completed && !localCompletedLessons.value.has(lesson.id)) {
    localCompletedLessons.value.add(lesson.id)
  }

  // Start 5-minute timer for this lesson
  if (!lesson.is_completed) {
    completionTimers.value[lesson.id] = setTimeout(() => {
      // Send completion to server after 5 minutes
      router.post(`/member/classroom/${props.course.id}/progress/${lesson.id}`, {}, {
        preserveScroll: true,
        onSuccess: () => {
          // Server confirmed, update actual state
          lesson.is_completed = true
        }
      })
    }, COMPLETION_THRESHOLD_MS)
  }
}

const markIncomplete = (lesson) => {
  // Cancel timer if exists
  if (completionTimers.value[lesson.id]) {
    clearTimeout(completionTimers.value[lesson.id])
    delete completionTimers.value[lesson.id]
  }

  // Remove from local optimistic state
  localCompletedLessons.value.delete(lesson.id)

  // Immediately send to server (no throttling for uncomplete)
  router.delete(`/member/classroom/${props.course.id}/progress/${lesson.id}`, {
    preserveScroll: true
  })
}

// Cleanup timers on component unmount
onUnmounted(() => {
  Object.values(completionTimers.value).forEach(timer => clearTimeout(timer))
})

// Check if lesson shows as completed (either server or optimistic)
const isLessonCompleted = (lesson) => {
  return lesson.is_completed || localCompletedLessons.value.has(lesson.id)
}
</script>
```

### Key Behaviors
1. **樂觀更新**: 點擊小節後，前端立即顯示綠色勾勾
2. **5分鐘門檻**: 計時器到期後才發送 POST 請求至伺服器
3. **切換取消**: 5分鐘內切換至其他小節，取消原小節的計時器
4. **取消完成立即發送**: 點擊勾勾取消完成時，立即發送 DELETE 請求
5. **頁面離開**: 未達門檻的進度不會被記錄（計時器被清除）
6. **重新載入**: 頁面重新載入時從伺服器獲取真實狀態

### Edge Cases Handling
- **網路斷線**: 計時器到期但無法送出請求時，透過 Inertia.js 的錯誤處理機制處理
- **快速來回點擊**: 每次點擊同一小節都重新開始計時
- **已完成小節**: 點擊已在伺服器記錄為完成的小節時，不重複發送請求

### Alternatives Considered
- **後端計時**: 需要 WebSocket 或定期輪詢，增加伺服器負載
- **累積批次送出**: 複雜度高，且用戶離開時可能遺失進度
- **localStorage 暫存**: 跨裝置同步問題

---

## Auto-Assign Course Ownership to Admin (2026-01-26 新增)

### Decision
課程建立時自動為管理員建立購買紀錄，使用 `system_assigned` 購買類型標記。

### Rationale
- 管理員需要以用戶視角測試課程，必須擁有課程存取權
- 自動指派避免手動建立購買紀錄的繁瑣操作
- 使用專門的類型標記，便於區分真實購買和系統指派

### Implementation
```php
// Admin/CourseController@store
public function store(StoreCourseRequest $request)
{
    $course = Course::create($request->validated());

    // Auto-assign ownership to admin who created the course
    Purchase::create([
        'user_id' => auth()->id(),
        'course_id' => $course->id,
        'type' => 'system_assigned',
        'amount' => 0,
        'currency' => 'TWD',
        'status' => 'paid',
        'portaly_order_id' => 'SYSTEM-' . Str::uuid(),
    ]);

    return redirect()->route('admin.courses.edit', $course);
}
```

### Purchase Type Extension
```php
// Migration: add_type_to_purchases_table.php
Schema::table('purchases', function (Blueprint $table) {
    $table->string('type')->default('paid')->after('status');
    // Values: 'paid', 'system_assigned', 'gift'
});
```

### Alternatives Considered
- **Separate admin_access table**: 過度複雜，購買紀錄已能滿足需求
- **Role-based access override**: 不夠細緻，無法追蹤哪些課程是誰建立的
- **No auto-assign**: 管理員需手動建立購買紀錄，效率低下

---

## Admin Frontend Course Preview (2026-01-26 新增)

### Decision
管理員登入後，首頁和課程販售頁顯示所有課程（含草稿），使用條件查詢根據用戶角色返回不同資料。

### Rationale
- 管理員需要在正式上架前以用戶視角檢查課程
- 前端條件渲染比建立獨立預覽路由更簡單
- 符合 Constitution 的簡單優先原則

### Implementation
```php
// HomeController@index
public function index()
{
    $user = auth()->user();

    $courses = Course::query()
        ->when($user && $user->role === 'admin', function ($query) {
            // Admin sees all courses including drafts
            return $query->orderBy('sort_order');
        }, function ($query) {
            // Regular users only see visible courses
            return $query->visible()->orderBy('sort_order');
        })
        ->get();

    return Inertia::render('Home', [
        'courses' => $courses,
        'isAdmin' => $user && $user->role === 'admin',
    ]);
}

// CourseController@show
public function show(Course $course)
{
    $user = auth()->user();
    $isAdmin = $user && $user->role === 'admin';

    // Non-admin trying to access draft course
    if (!$isAdmin && $course->status === 'draft') {
        abort(404);
    }

    return Inertia::render('Course/Show', [
        'course' => $course,
        'isAdmin' => $isAdmin,
        'isPreviewMode' => $course->status === 'draft',
    ]);
}
```

### Frontend Components
```vue
<!-- Home.vue - Course card with draft badge -->
<div v-if="isAdmin && course.status === 'draft'"
     class="absolute top-2 left-2 px-2 py-1 bg-gray-500 text-white text-xs rounded">
  草稿
</div>

<!-- Course/Show.vue - Preview mode banner -->
<div v-if="isPreviewMode"
     class="fixed top-0 left-0 right-0 bg-blue-500 text-white text-center py-2 z-50">
  預覽模式 - 此課程尚未上架，僅管理員可見
</div>

<!-- Purchase button for draft courses -->
<button
  v-if="isPreviewMode"
  @click="showPreviewAlert = true"
  class="w-full py-3 bg-gray-400 text-white rounded-lg">
  購買（草稿課程，僅供預覽）
</button>
```

### Alternatives Considered
- **獨立預覽路由 /admin/preview/{course}**: 需要額外路由和邏輯，且無法測試真實用戶體驗
- **預覽 Token 機制**: 複雜度高，且不符合「管理員直接預覽」的使用場景
- **前端環境變數控制**: 無法根據用戶角色動態調整

---

## Course Visibility Toggle (2026-01-30 新增)

### Decision
使用 `is_visible` boolean 欄位控制課程是否顯示在首頁列表。隱藏課程仍可透過直接 URL 存取和購買。

### Rationale
- 簡單的 boolean 欄位足以滿足「顯示/隱藏」需求
- 與現有 `visible()` scope 整合，只需修改 scope 條件
- 隱藏課程仍需可購買，不影響 `CourseController@show` 存取邏輯
- 管理員需要看到隱藏課程，已有 `visibleToUser()` scope 處理角色邏輯

### Implementation
```php
// Course.php - 更新 visible scope
public function scopeVisible($query)
{
    return $query
        ->whereIn('status', ['preorder', 'selling'])
        ->where('is_published', true)
        ->where('is_visible', true);  // 新增此條件
}

// visibleToUser scope 無需修改，因為 admin 已經看到所有課程
public function scopeVisibleToUser($query, $user = null)
{
    if ($user && $user->role === 'admin') {
        return $query; // Admin sees all (including hidden)
    }
    return $query->visible(); // Non-admin uses visible() which now includes is_visible check
}
```

### UI Display Logic
```vue
<!-- Home.vue - Hidden badge for admin -->
<div v-if="isAdmin && !course.is_visible"
     class="text-gray-500 italic text-xs">
  隱藏
</div>

<!-- Admin CourseForm.vue -->
<label class="flex items-center gap-2">
  <input type="checkbox" v-model="form.is_visible" />
  <span>是否顯示在首頁</span>
</label>
<p class="text-sm text-gray-500">
  關閉後課程不會出現在首頁，但仍可透過網址存取和購買
</p>
```

### Edge Cases
- **隱藏 + 草稿**：草稿限制優先，一般用戶無法存取
- **隱藏 + 已購買**：正常顯示在「我的課程」（不受 is_visible 影響）
- **管理員視角**：首頁顯示「隱藏」標籤，後臺顯示顯示狀態欄位

### Alternatives Considered
- **使用 status 增加 'hidden' 值**: 違反 status 語義（draft/preorder/selling 是發佈狀態，hidden 是曝光控制）
- **使用獨立 visibility_level 欄位 (public/private/unlisted)**: 過度設計，目前只需 boolean
- **僅允許透過特殊 token 存取隱藏課程**: 複雜度高，不符合「可透過 URL 購買」需求

---

## Summary

| Topic | Decision | Package/Tool |
|-------|----------|--------------|
| Video Embed | URL parsing + iframe | None (regex) |
| Drag & Drop | SortableJS wrapper | vuedraggable@next |
| Image Storage | Local storage | Laravel Storage |
| Status Scheduler | Task Scheduling | Laravel built-in |
| Admin Auth | Custom middleware | None |
| HTML Content | No sanitization | None |
| **Countdown Timer** | Frontend Vue computed | None (native JS) |
| **Image Gallery Modal** | Vue 3 Modal + Teleport | None (native Vue) |
| **Legal Policy Modal** | Static Vue component | None (native Vue) |
| **Lesson Completion Throttle** | Frontend setTimeout | None (native JS) |
| **Auto-Assign Ownership** | Purchase type extension | None (Laravel) |
| **Admin Frontend Preview** | Conditional query + UI badges | None (Vue conditional) |
| **Course Visibility Toggle** | is_visible boolean field | None (Laravel scope) |
