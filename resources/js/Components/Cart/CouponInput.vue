<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  // 當前購物車的 course_id 陣列（登入取伺服器、訪客取 localStorage）
  courseIds: { type: Array, default: () => [] },
  // US5：網址 ?coupon= 經 session 帶入的原始代碼字串；有值則 onMounted 自動套用
  prefillCode: { type: String, default: null },
})

const emit = defineEmits(['applied', 'removed', 'invalidated'])

const code = ref('')
const applied = ref(null)   // { code, type, label, discount, original, payable }
const error = ref('')
const loading = ref(false)

const apply = async (rawCode, { silent = false } = {}) => {
  const value = (rawCode ?? '').toString().trim().toUpperCase()
  if (!value || loading.value) return

  loading.value = true
  error.value = ''
  try {
    const res = await window.axios.post('/api/cart/apply-coupon', {
      code: value,
      course_ids: props.courseIds,
    })
    applied.value = res.data
    code.value = ''
    emit('applied', res.data)
  } catch (e) {
    // US5：自動帶入（silent）失敗時靜默忽略，不顯示錯誤
    if (!silent) {
      error.value = e.response?.data?.message || '套用失敗，請稍後再試'
    }
  } finally {
    loading.value = false
  }
}

const remove = async () => {
  applied.value = null
  error.value = ''
  emit('removed')
  // 清除 session 中的自動帶入碼，避免重整後再次自動套用（US5-4）
  try {
    await window.axios.delete('/api/cart/coupon')
  } catch {
    // 清除失敗非關鍵
  }
}

// US1-9：購物車內容變更後重新驗證已套用的折扣碼；若失效則清除並通知父層
const revalidate = async (courseIdsOverride = null) => {
  if (!applied.value) return
  const ids = courseIdsOverride ?? props.courseIds
  try {
    const res = await window.axios.post('/api/cart/apply-coupon', {
      code: applied.value.code,
      course_ids: ids,
    })
    applied.value = res.data
    emit('applied', res.data)
  } catch {
    applied.value = null
    emit('invalidated')
  }
}

defineExpose({ revalidate })

// US5：自動帶入。courseIds 可能於父層 onMounted 才載入（訪客購物車），
// 故以 watch（immediate）在 courseIds 就緒時觸發一次，而非僅依賴 onMounted。
let prefillAttempted = false
watch(
  () => props.courseIds,
  () => {
    if (prefillAttempted || applied.value) return
    if (props.prefillCode && props.courseIds.length > 0) {
      prefillAttempted = true
      apply(props.prefillCode, { silent: true })
    }
  },
  { immediate: true, deep: true },
)
</script>

<template>
  <div class="rounded-xl bg-white border border-gray-100 shadow-sm p-4">
    <!-- 已套用狀態 -->
    <div v-if="applied" class="flex items-center justify-between gap-3">
      <div class="min-w-0">
        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-brand-teal/10 text-brand-teal text-sm font-semibold">
          {{ applied.label }}
        </span>
        <p class="text-xs text-gray-500 mt-1">已套用代碼：{{ applied.code }}</p>
      </div>
      <button
        @click="remove"
        class="text-sm text-gray-400 hover:text-red-500 transition-colors shrink-0"
      >
        移除
      </button>
    </div>

    <!-- 輸入狀態 -->
    <div v-else>
      <label class="block text-sm font-medium text-gray-600 mb-2">折扣碼</label>
      <div class="flex gap-2">
        <input
          v-model="code"
          type="text"
          maxlength="6"
          placeholder="輸入折扣碼"
          class="flex-1 min-w-0 rounded-lg border border-gray-200 px-3 py-2 text-sm uppercase focus:border-brand-teal focus:ring-1 focus:ring-brand-teal outline-none"
          @keyup.enter="apply(code)"
        />
        <button
          @click="apply(code)"
          :disabled="loading || !code.trim()"
          class="px-4 py-2 rounded-lg font-semibold text-sm bg-brand-navy text-white hover:bg-brand-navy/90 transition-colors disabled:opacity-40 shrink-0"
        >
          套用
        </button>
      </div>
      <p v-if="error" class="text-sm text-red-600 mt-2">{{ error }}</p>
    </div>
  </div>
</template>
