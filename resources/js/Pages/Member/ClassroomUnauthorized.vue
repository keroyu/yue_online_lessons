<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Components/Layout/AppLayout.vue'

defineOptions({
  layout: AppLayout,
})

defineProps({
  course: {
    type: Object,
    required: true,
  },
  message: {
    type: String,
    default: '您尚未購買此課程',
  },
})
</script>

<template>
  <div class="max-w-2xl mx-auto px-4 py-16 text-center">
    <Head :title="message" />

    <div class="bg-white rounded-lg shadow-sm p-8">
      <div class="w-16 h-16 mx-auto mb-6 bg-yellow-100 rounded-full flex items-center justify-center">
        <svg class="w-8 h-8 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
        </svg>
      </div>

      <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ message }}</h1>
      <p class="text-gray-500 mb-6">請先購買課程後再來上課。</p>

      <div class="flex flex-col sm:flex-row gap-3 justify-center">
        <Link
          :href="`/course/${course.id}`"
          class="inline-flex items-center justify-center px-6 py-3 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-colors"
        >
          前往購買
        </Link>
        <Link
          href="/member/learning"
          class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors"
        >
          我的課程
        </Link>
      </div>
    </div>
  </div>
</template>
