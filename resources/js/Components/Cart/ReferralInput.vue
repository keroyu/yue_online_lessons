<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  // 買家 Email（用於自薦檢查；伺服器於建單前會再次驗證）
  buyerEmail: { type: String, default: '' },
})

const emit = defineEmits(['applied', 'removed'])

const code = ref('')
const applied = ref(null)   // { code, rate }
const error = ref('')
const loading = ref(false)

const apply = async (rawCode) => {
  const value = (rawCode ?? '').toString().trim().toUpperCase()
  if (!value || loading.value) return

  loading.value = true
  error.value = ''
  try {
    const res = await window.axios.post('/api/checkout/validate-referral', {
      referral_code: value,
      buyer_email: props.buyerEmail || null,
    })
    applied.value = { code: value, rate: res.data.rate, discount: res.data.discount ?? 0 }
    code.value = ''
    emit('applied', applied.value)
  } catch (e) {
    error.value = e.response?.data?.message || '推薦碼驗證失敗，請稍後再試'
  } finally {
    loading.value = false
  }
}

const remove = () => {
  applied.value = null
  error.value = ''
  emit('removed')
}

// 買家改 Email 後，已套用的推薦碼可能變成自薦；清掉讓使用者重新輸入，避免帶錯。
watch(() => props.buyerEmail, () => {
  if (applied.value) remove()
})
</script>

<template>
  <div class="rounded-xl bg-white border border-gray-100 shadow-sm p-4">
    <!-- 已套用狀態 -->
    <div v-if="applied" class="flex items-center justify-between gap-3">
      <div class="min-w-0">
        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-brand-teal/10 text-brand-teal text-sm font-semibold">
          推薦碼 {{ applied.code }}
        </span>
        <p class="text-xs text-gray-500 mt-1">
          <template v-if="applied.discount > 0">訂單已折抵 NT$ {{ applied.discount.toLocaleString() }}（低價訂單以實付 1 元為底）；</template>完成付款後，推薦人將獲得回饋積分
        </p>
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
      <label class="block text-sm font-medium text-gray-600 mb-2">推薦碼 <span class="text-gray-400 font-normal">（選填）</span></label>
      <div class="flex gap-2">
        <input
          v-model="code"
          type="text"
          maxlength="12"
          placeholder="輸入好友的推薦碼"
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
