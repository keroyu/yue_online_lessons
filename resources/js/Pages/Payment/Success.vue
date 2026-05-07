<script setup>
import { onMounted, onUnmounted, ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'

defineOptions({ layout: false })

const props = defineProps({
  order:     { type: Object,  default: null },
  isLoggedIn:{ type: Boolean, default: false },
  waiting:   { type: Boolean, default: false },
})

let pollTimer = null
const pollTimedOut = ref(false)
const POLL_INTERVAL_MS = 2000
const POLL_MAX_ATTEMPTS = 30 // 30 × 2s = 60s

onMounted(() => {
  if (props.waiting) {
    const orderNo = new URLSearchParams(window.location.search).get('order')
    let attempts = 0
    pollTimer = setInterval(async () => {
      attempts++
      if (attempts > POLL_MAX_ATTEMPTS) {
        clearInterval(pollTimer)
        pollTimedOut.value = true
        return
      }
      try {
        const res = await window.axios.get('/api/checkout/order-status', { params: { order: orderNo } })
        if (res.data.status === 'paid') {
          clearInterval(pollTimer)
          window.location.reload()
        }
      } catch { /* ignore */ }
    }, POLL_INTERVAL_MS)
    return
  }

  // Clear guest cart
  localStorage.removeItem('guest_cart')

  // Meta Pixel Purchase event
  if (window.fbq && props.order) {
    window.fbq('track', 'Purchase', {
      value:        parseFloat(props.order.total_amount),
      currency:     'TWD',
      content_ids:  props.order.items.map((i) => i.course_id),
      content_type: 'product',
      num_items:    props.order.items.length,
    }, { eventID: `purchase_${props.order.merchant_order_no}` })
  }
})

onUnmounted(() => {
  if (pollTimer) clearInterval(pollTimer)
})

const formatPrice = (val) => {
  const num = parseFloat(val)
  return isNaN(num) ? '0' : num.toLocaleString()
}
</script>

<template>
  <AppLayout>
    <Head :title="waiting ? '確認付款中' : '付款成功'" />

    <!-- Waiting overlay -->
    <Teleport v-if="waiting" to="body">
      <div class="fixed inset-0 z-50 flex flex-col items-center justify-center bg-white/80 backdrop-blur-sm px-6">
        <template v-if="!pollTimedOut">
          <svg class="animate-spin w-12 h-12 text-brand-teal mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
          </svg>
          <p class="text-brand-navy font-semibold text-lg">正在確認付款結果…</p>
          <p class="text-gray-500 text-sm mt-1">請稍候，勿關閉此頁面</p>
        </template>
        <template v-else>
          <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-amber-100 mb-4">
            <svg class="w-7 h-7 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <p class="text-brand-navy font-semibold text-lg text-center">付款結果尚未確認</p>
          <p class="text-gray-600 text-sm mt-2 text-center max-w-sm">
            金流系統處理時間較久。您的款項若已扣款，課程將在數分鐘內自動開通。
          </p>
          <p class="text-gray-500 text-xs mt-3 text-center">
            如有疑問請聯絡客服 <a href="mailto:themustbig+learn@gmail.com" class="underline">themustbig+learn@gmail.com</a>
          </p>
          <div class="mt-5 flex gap-3">
            <button
              @click="() => window.location.reload()"
              class="px-4 py-2 rounded-lg bg-brand-teal text-white text-sm font-semibold hover:bg-brand-teal/80 transition-colors"
            >
              重新確認
            </button>
            <Link
              :href="isLoggedIn ? '/member/learning' : '/'"
              class="px-4 py-2 rounded-lg border border-gray-300 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors"
            >
              {{ isLoggedIn ? '前往我的課程' : '回首頁' }}
            </Link>
          </div>
        </template>
      </div>
    </Teleport>

    <div v-if="!waiting" class="max-w-lg mx-auto px-4 py-12">
      <!-- Success icon -->
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-100 mb-4">
          <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
        </div>
        <h1 class="text-2xl font-bold text-brand-navy">付款成功！</h1>
        <p class="text-gray-500 mt-1 text-sm">感謝您的購買，課程已為您開通</p>
      </div>

      <!-- Order details -->
      <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 space-y-4">
        <h2 class="font-semibold text-brand-navy">訂單資訊</h2>

        <dl class="space-y-2 text-sm">
          <div class="flex justify-between">
            <dt class="text-gray-500">訂單編號</dt>
            <dd class="font-mono text-gray-700">{{ order.merchant_order_no }}</dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-gray-500">姓名</dt>
            <dd class="text-gray-700">{{ order.buyer_name }}</dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-gray-500">Email</dt>
            <dd class="text-gray-700">{{ order.buyer_email }}</dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-gray-500">電話</dt>
            <dd class="text-gray-700">{{ order.buyer_phone }}</dd>
          </div>
          <div v-if="order.tax_id" class="flex justify-between">
            <dt class="text-gray-500">公司統編</dt>
            <dd class="text-gray-700 font-mono">{{ order.tax_id }}</dd>
          </div>
        </dl>

        <!-- Items -->
        <div class="pt-3 border-t border-gray-100 space-y-2">
          <div
            v-for="(item, idx) in order.items"
            :key="idx"
            class="flex justify-between text-sm"
          >
            <span class="text-gray-700 truncate mr-4">{{ item.course_name }}</span>
            <span class="font-medium shrink-0">NT$ {{ formatPrice(item.unit_price) }}</span>
          </div>
        </div>

        <!-- Total -->
        <div class="pt-3 border-t border-gray-100 flex justify-between font-bold">
          <span>總金額</span>
          <span class="text-brand-teal">NT$ {{ formatPrice(order.total_amount) }}</span>
        </div>
      </div>

      <!-- CTA -->
      <div class="mt-6">
        <Link
          v-if="isLoggedIn"
          href="/member/learning"
          class="block w-full text-center py-3 rounded-lg font-semibold bg-brand-teal text-white hover:bg-brand-teal/80 transition-all shadow-sm"
        >
          前往我的課程
        </Link>
        <Link
          v-else
          href="/login?hint=purchase"
          class="block w-full text-center py-3 rounded-lg font-semibold bg-brand-gold hover:bg-brand-gold-dark text-brand-navy border border-brand-gold-dark/50 transition-all shadow-sm"
        >
          登入查看課程
        </Link>
      </div>
    </div>
  </AppLayout>
</template>
