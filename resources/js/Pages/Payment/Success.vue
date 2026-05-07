<script setup>
import { onMounted, onUnmounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'

defineOptions({ layout: false })

const props = defineProps({
  order:     { type: Object,  default: null },
  isLoggedIn:{ type: Boolean, default: false },
  waiting:   { type: Boolean, default: false },
})

let pollTimer = null

onMounted(() => {
  if (props.waiting) {
    const orderNo = new URLSearchParams(window.location.search).get('order')
    pollTimer = setInterval(async () => {
      try {
        const res = await window.axios.get('/api/checkout/order-status', { params: { order: orderNo } })
        if (res.data.status === 'paid') {
          clearInterval(pollTimer)
          window.location.reload()
        }
      } catch { /* ignore */ }
    }, 2000)
    return
  }

  // Clear guest cart
  localStorage.removeItem('guest_cart')

  // Meta Pixel Purchase event
  if (window.fbq && props.order) {
    window.fbq('track', 'Purchase', {
      value:        parseFloat(props.order.total_amount),
      currency:     'TWD',
      content_ids:  props.order.items.map((_, i) => i),
    })
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
      <div class="fixed inset-0 z-50 flex flex-col items-center justify-center bg-white/80 backdrop-blur-sm">
        <svg class="animate-spin w-12 h-12 text-brand-teal mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
        </svg>
        <p class="text-brand-navy font-semibold text-lg">正在確認付款結果…</p>
        <p class="text-gray-500 text-sm mt-1">請稍候，勿關閉此頁面</p>
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
