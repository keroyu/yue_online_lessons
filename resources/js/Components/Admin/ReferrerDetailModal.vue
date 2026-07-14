<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue'

// Use the globally configured axios (CSRF token) — same pattern as MemberDetailModal.
const axios = window.axios

const props = defineProps({
  show: { type: Boolean, default: false },
  referrerId: { type: Number, default: null },
})

const emit = defineEmits(['close'])

const loading = ref(false)
const error = ref(null)
const referrer = ref(null)
const pointTransactions = ref([])
const ownTransactions = ref([])
const referredOrders = ref([])

// Fetch on open; reset on close.
watch(() => [props.show, props.referrerId], async ([show, id]) => {
  if (show && id) {
    await fetchDetail()
  } else {
    referrer.value = null
    pointTransactions.value = []
    ownTransactions.value = []
    referredOrders.value = []
    error.value = null
  }
}, { immediate: true })

const fetchDetail = async () => {
  loading.value = true
  error.value = null
  try {
    const { data } = await axios.get(`/admin/referrals/${props.referrerId}/detail`)
    referrer.value = data.referrer
    pointTransactions.value = data.point_transactions ?? []
    ownTransactions.value = data.own_transactions ?? []
    referredOrders.value = data.referred_orders ?? []
  } catch (err) {
    error.value = '載入推薦人明細失敗，請稍後再試'
  } finally {
    loading.value = false
  }
}

// Shared label maps (point types mirror MemberDetailModal).
const POINT_TYPE_LABELS = {
  earn_homework: '作業獎勵',
  redeem_course: '兌換課程',
  earn_referral: '推薦回饋',
  refund_reversal: '退款回收',
  admin_grant: '後台派發',
}
const pointTypeLabel = (t) => POINT_TYPE_LABELS[t] ?? t

const STATUS_LABELS = {
  paid: '已付款',
  pending: '待付款',
  failed: '失敗',
  refunded: '已退款',
}
const statusLabel = (s) => STATUS_LABELS[s] ?? s
const statusClass = (s) => ({
  paid: 'bg-green-50 text-green-700',
  pending: 'bg-amber-50 text-amber-700',
  failed: 'bg-gray-100 text-gray-500',
  refunded: 'bg-red-50 text-red-600',
}[s] ?? 'bg-gray-100 text-gray-500')

const fmtMoney = (n) => 'NT$ ' + Number(n || 0).toLocaleString()
const fmtDateTime = (d) => d ? new Date(d).toLocaleString('zh-TW') : '-'

// ESC to close + body scroll lock (same UX as MemberDetailModal).
const handleKeydown = (e) => {
  if (e.key === 'Escape' && props.show) emit('close')
}
watch(() => props.show, (v) => {
  document.body.style.overflow = v ? 'hidden' : ''
})
onMounted(() => document.addEventListener('keydown', handleKeydown))
onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown)
  document.body.style.overflow = ''
})

const handleBackdropClick = (e) => {
  if (e.target === e.currentTarget) emit('close')
}
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100"
      leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0"
    >
      <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto" @click="handleBackdropClick">
        <div class="fixed inset-0 bg-black/50" aria-hidden="true" />

        <div class="flex min-h-full items-center justify-center p-4">
          <div class="relative bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[85vh] flex flex-col" @click.stop>
            <!-- Header -->
            <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center rounded-t-lg">
              <div class="min-w-0">
                <h2 class="text-xl font-bold text-gray-900">推薦人明細</h2>
                <p v-if="referrer" class="text-sm text-gray-500 truncate">
                  {{ referrer.name }} · {{ referrer.email }}
                  <span class="font-mono text-gray-400">（{{ referrer.referral_code }}）</span>
                </p>
              </div>
              <button
                type="button"
                class="text-gray-400 hover:text-gray-600 transition-colors p-1 rounded-full hover:bg-gray-100 shrink-0"
                @click="emit('close')"
              >
                <span class="sr-only">關閉</span>
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <!-- Body -->
            <div class="flex-1 overflow-y-auto px-6 py-4">
              <div v-if="loading" class="flex items-center justify-center py-12 text-gray-500">
                <svg class="animate-spin h-6 w-6 text-brand-teal mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                載入中…
              </div>

              <div v-else-if="error" class="py-12 text-center text-sm text-gray-600">{{ error }}</div>

              <div v-else-if="referrer" class="space-y-6">
                <!-- (1) 積分帳本 -->
                <section>
                  <div class="flex items-center justify-between mb-3">
                    <h3 class="text-base font-semibold text-gray-900">積分帳本</h3>
                    <span class="text-sm font-bold text-green-600">可用 {{ referrer.current_points.toLocaleString() }} 分</span>
                  </div>
                  <div v-if="pointTransactions.length === 0" class="text-center py-5 bg-gray-50 rounded-lg text-sm text-gray-500">尚無積分紀錄</div>
                  <div v-else class="space-y-2 max-h-56 overflow-y-auto">
                    <div v-for="(tx, i) in pointTransactions" :key="i" class="flex items-center justify-between bg-gray-50 rounded-lg px-4 py-2.5 text-sm">
                      <div class="min-w-0">
                        <span class="font-medium text-gray-900">{{ pointTypeLabel(tx.type) }}</span>
                        <span v-if="!tx.is_matured" class="ml-2 text-xs text-amber-600">未成熟</span>
                        <p class="text-xs text-gray-400 mt-0.5">{{ fmtDateTime(tx.created_at) }}<span v-if="tx.note"> · {{ tx.note }}</span></p>
                      </div>
                      <span class="font-bold shrink-0 ml-3" :class="tx.amount >= 0 ? 'text-green-600' : 'text-red-500'">
                        {{ tx.amount >= 0 ? '+' : '' }}{{ tx.amount }}
                      </span>
                    </div>
                  </div>
                </section>

                <!-- (2) 本人交易紀錄 -->
                <section class="border-t border-gray-200 pt-6">
                  <h3 class="text-base font-semibold text-gray-900 mb-3">本人交易紀錄</h3>
                  <div v-if="ownTransactions.length === 0" class="text-center py-5 bg-gray-50 rounded-lg text-sm text-gray-500">尚無交易紀錄</div>
                  <div v-else class="space-y-2 max-h-56 overflow-y-auto">
                    <div v-for="tx in ownTransactions" :key="tx.id" class="flex items-center justify-between bg-gray-50 rounded-lg px-4 py-2.5 text-sm gap-3">
                      <div class="min-w-0">
                        <p class="font-medium text-gray-900 truncate">{{ tx.course_name || '（課程已刪除）' }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">
                          {{ fmtDateTime(tx.created_at) }}
                          <span v-if="tx.merchant_order_no"> · {{ tx.merchant_order_no }}</span>
                          · {{ tx.type_label }}
                        </p>
                      </div>
                      <div class="shrink-0 text-right">
                        <p class="font-semibold text-gray-800">{{ fmtMoney(tx.amount) }}</p>
                        <span class="inline-block mt-0.5 px-1.5 py-0.5 rounded text-xs font-medium" :class="statusClass(tx.status)">{{ statusLabel(tx.status) }}</span>
                      </div>
                    </div>
                  </div>
                </section>

                <!-- (3) 帶進來的推薦訂單 -->
                <section class="border-t border-gray-200 pt-6">
                  <h3 class="text-base font-semibold text-gray-900 mb-3">帶進來的推薦訂單</h3>
                  <div v-if="referredOrders.length === 0" class="text-center py-5 bg-gray-50 rounded-lg text-sm text-gray-500">尚無推薦訂單</div>
                  <div v-else class="space-y-2 max-h-56 overflow-y-auto">
                    <div v-for="o in referredOrders" :key="o.id" class="flex items-center justify-between bg-gray-50 rounded-lg px-4 py-2.5 text-sm gap-3">
                      <div class="min-w-0">
                        <p class="font-medium text-gray-900 truncate">{{ o.buyer_email }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">
                          {{ fmtDateTime(o.created_at) }}
                          <span v-if="o.merchant_order_no"> · {{ o.merchant_order_no }}</span>
                        </p>
                      </div>
                      <div class="shrink-0 text-right">
                        <p class="font-semibold text-gray-800">{{ fmtMoney(o.total_amount) }}</p>
                        <p class="text-xs text-brand-teal font-medium">回饋 {{ Number(o.referral_reward_points || 0).toLocaleString() }} 分</p>
                        <span class="inline-block mt-0.5 px-1.5 py-0.5 rounded text-xs font-medium" :class="statusClass(o.status)">{{ statusLabel(o.status) }}</span>
                      </div>
                    </div>
                  </div>
                </section>
              </div>
            </div>

            <!-- Footer -->
            <div class="border-t border-gray-200 px-6 py-4 bg-gray-50 rounded-b-lg">
              <button type="button" class="w-full sm:w-auto px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors" @click="emit('close')">關閉</button>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
