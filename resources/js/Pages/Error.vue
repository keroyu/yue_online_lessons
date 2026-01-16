<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
  status: {
    type: Number,
    required: true,
  },
})

const title = {
  404: '頁面不存在',
  403: '沒有權限',
  500: '伺服器錯誤',
  503: '服務暫時無法使用',
}

const description = {
  404: '您要找的頁面不存在或已被移除。',
  403: '您沒有權限訪問此頁面。',
  500: '抱歉，伺服器發生錯誤，請稍後再試。',
  503: '服務暫時無法使用，請稍後再試。',
}
</script>

<template>
  <div class="min-h-screen bg-gray-50 flex flex-col items-center justify-center px-4">
    <div class="text-center">
      <!-- Error Code -->
      <h1 class="text-9xl font-bold text-indigo-600">
        {{ status }}
      </h1>

      <!-- Title -->
      <h2 class="mt-4 text-2xl font-semibold text-gray-900">
        {{ title[status] || '發生錯誤' }}
      </h2>

      <!-- Description -->
      <p class="mt-2 text-gray-500 max-w-md mx-auto">
        {{ description[status] || '抱歉，發生了一些問題，請稍後再試。' }}
      </p>

      <!-- Actions -->
      <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-center">
        <Link
          href="/"
          class="inline-flex items-center justify-center px-6 py-3 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-colors"
        >
          返回首頁
        </Link>
        <button
          @click="$inertia.reload()"
          class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors"
        >
          重新整理
        </button>
      </div>
    </div>
  </div>
</template>
