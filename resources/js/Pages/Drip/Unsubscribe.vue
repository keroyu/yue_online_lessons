<script setup>
import { Head, router } from '@inertiajs/vue3'
import { ref } from 'vue'

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

const confirmUnsubscribe = () => {
  processing.value = true
  router.post(`/drip/unsubscribe/${props.token}`, {}, {
    onFinish: () => {
      processing.value = false
    },
  })
}
</script>

<template>
  <Head title="退訂確認" />

  <div class="min-h-screen bg-gray-100 flex items-center justify-center px-4">
    <div class="max-w-md w-full bg-white rounded-lg shadow-sm p-8">
      <!-- Already unsubscribed -->
      <div v-if="subscription.status === 'unsubscribed'" class="text-center">
        <div class="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
          <svg class="w-8 h-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
        </div>
        <h1 class="text-xl font-semibold text-gray-900 mb-2">已退訂</h1>
        <p class="text-gray-600 mb-6">您已退訂「{{ subscription.course_name }}」課程。</p>
        <a href="/" class="text-indigo-600 hover:underline text-sm">返回首頁</a>
      </div>

      <!-- Confirm unsubscribe -->
      <div v-else class="text-center">
        <div class="mx-auto w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mb-4">
          <svg class="w-8 h-8 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
        </div>

        <h1 class="text-xl font-semibold text-gray-900 mb-2">確認退訂</h1>
        <p class="text-gray-900 font-medium mb-4">{{ subscription.course_name }}</p>

        <!-- Warning -->
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
          <p class="text-sm text-red-800">
            這是限期商品，一旦退訂將無法再次訂閱此課程。已解鎖的內容仍可觀看，但不會再收到後續信件。
          </p>
        </div>

        <div class="flex flex-col gap-3">
          <button
            @click="confirmUnsubscribe"
            :disabled="processing"
            class="w-full px-6 py-3 bg-red-600 text-white rounded-lg font-medium hover:bg-red-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {{ processing ? '處理中...' : '確認退訂' }}
          </button>
          <a
            href="/"
            class="w-full px-6 py-3 text-gray-700 bg-gray-100 rounded-lg font-medium hover:bg-gray-200 transition-colors text-center"
          >
            取消
          </a>
        </div>
      </div>
    </div>
  </div>
</template>
