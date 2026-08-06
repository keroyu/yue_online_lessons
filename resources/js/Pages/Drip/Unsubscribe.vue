<script setup>
import { Head, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import { useDelayedConfirm } from '@/composables/useDelayedConfirm'

const props = defineProps({
  subscription: {
    type: Object,
    required: true,
  },
  token: {
    type: String,
    required: true,
  },
})

const processing = ref(false)

// Two-stage confirm (010 FR-028). Most people press this to get fewer emails;
// they do not know it also ends their claim eligibility. The pause is what
// makes the paragraph above the button get read — an explanation nobody reads
// is the same as no explanation. Whoever still wants out is out in 5 seconds.
//
// This lives on the page only. The POST endpoint stays single-request because
// it doubles as the RFC 8058 one-click target: mail clients post straight to
// it with no page to press twice.
const { confirming, countdown, start: startConfirm } = useDelayedConfirm(5)

const confirmLabel = computed(() => {
  if (processing.value) return '處理中...'
  if (countdown.value > 0) return `請再想一下（${countdown.value}）`
  if (confirming.value) return '我確定，停止接收'
  return '確定停止接收'
})

const confirmUnsubscribe = () => {
  if (!startConfirm()) return

  processing.value = true
  router.post(`/drip/unsubscribe/${props.token}`, {}, {
    onFinish: () => {
      processing.value = false
    },
  })
}
</script>

<template>
  <Head title="停止接收確認" />

  <div class="min-h-screen bg-gray-100 flex items-center justify-center px-4 py-10">
    <div class="max-w-md w-full bg-white rounded-lg shadow-sm p-8">
      <!-- Already unsubscribed -->
      <div v-if="subscription.status === 'unsubscribed'" class="text-center">
        <div class="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
          <svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
        </div>
        <h1 class="text-xl font-semibold text-gray-900 mb-2">已停止接收</h1>
        <p class="text-gray-600 mb-6">您已停止接收「{{ subscription.course_name }}」的信件。</p>
        <a href="/" class="text-indigo-600 hover:underline text-sm">返回首頁</a>
      </div>

      <!-- Confirm: explain what is actually being given up, then ask twice -->
      <div v-else class="text-center">
        <div class="mx-auto w-16 h-16 bg-amber-100 rounded-full flex items-center justify-center mb-4">
          <svg class="w-8 h-8 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>

        <h1 class="text-xl font-semibold text-gray-900 mb-2">停止接收前，請先了解</h1>
        <p class="text-gray-900 font-medium mb-4">{{ subscription.course_name }}</p>

        <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-6 text-left">
          <p class="text-sm leading-relaxed text-amber-900">
            本免費商品與後續免費資源，是提供給願意持續接收我們內容的人。若您選擇停止接收，我們會立即停止寄送信件；同時，您的領取資格也會終止，未來部分限定免費資源、活動及諮詢申請可能不再開放。付費產品與既有客戶權益不受影響。
          </p>
        </div>

        <div class="flex flex-col gap-3">
          <button
            @click="confirmUnsubscribe"
            :disabled="processing || countdown > 0"
            class="w-full px-6 py-3 bg-red-600 text-white rounded-lg font-medium hover:bg-red-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {{ confirmLabel }}
          </button>
          <!-- The exit stays open the whole way through: this is a pause, not a trap -->
          <a
            href="/"
            class="w-full px-6 py-3 text-gray-700 bg-gray-100 rounded-lg font-medium hover:bg-gray-200 transition-colors text-center"
          >
            取消，我要繼續接收
          </a>
        </div>
      </div>
    </div>
  </div>
</template>
