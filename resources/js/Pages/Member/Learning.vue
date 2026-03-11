<script setup>
import AppLayout from '@/Components/Layout/AppLayout.vue'
import MyCourseCard from '@/Components/MyCourseCard.vue'
import { Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

defineOptions({
  layout: AppLayout,
})

defineProps({
  courses: {
    type: Array,
    default: () => [],
  },
})

const page = usePage()
const isLoggedIn = computed(() => !!page.props.auth?.user)
</script>

<template>
  <div class="max-w-[1100px] mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-2xl sm:text-3xl font-bold text-brand-navy mb-6">
      我的課程
    </h1>

    <!-- Not logged in (client-side guard for Inertia SPA cache edge case) -->
    <div
      v-if="!isLoggedIn"
      class="text-center py-16"
    >
      <div class="mx-auto w-24 h-24 bg-brand-cream rounded-full flex items-center justify-center mb-4">
        <svg
          class="w-12 h-12 text-brand-navy/40"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
          />
        </svg>
      </div>
      <h2 class="text-xl font-semibold text-brand-navy mb-2">
        請先登入
      </h2>
      <p class="text-gray-500 mb-6">
        登入後即可查看您的課程內容。
      </p>
      <Link
        href="/login"
        class="inline-flex items-center px-6 py-3 bg-brand-teal text-white font-medium rounded-lg hover:bg-brand-teal/80 transition-colors"
      >
        前往登入
      </Link>
    </div>

    <!-- Course Grid -->
    <div
      v-else-if="courses.length > 0"
      class="grid grid-cols-1 sm:grid-cols-2 gap-6"
    >
      <MyCourseCard
        v-for="course in courses"
        :key="course.id"
        :course="course"
      />
    </div>

    <!-- Empty State (logged in, no courses yet) -->
    <div
      v-else
      class="text-center py-16"
    >
      <div class="mx-auto w-24 h-24 bg-brand-cream rounded-full flex items-center justify-center mb-4">
        <svg
          class="w-12 h-12 text-brand-navy/40"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
          />
        </svg>
      </div>
      <h2 class="text-xl font-semibold text-brand-navy mb-2">
        尚無課程
      </h2>
      <p class="text-gray-500 mb-6">
        您目前還沒有購買任何課程，立即探索精彩內容！
      </p>
      <Link
        href="/"
        class="inline-flex items-center px-6 py-3 bg-brand-teal text-white font-medium rounded-lg hover:bg-brand-teal/80 transition-colors"
      >
        瀏覽課程
      </Link>
    </div>
  </div>
</template>
