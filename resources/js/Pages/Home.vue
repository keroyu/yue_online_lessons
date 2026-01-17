<script setup>
import { Head } from '@inertiajs/vue3'
import CourseCard from '@/Components/CourseCard.vue'

defineProps({
  courses: {
    type: Array,
    required: true,
  },
})
</script>

<template>
  <Head title="首頁" />

  <div class="py-8 sm:py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Hero section -->
      <div class="text-center mb-10 sm:mb-16">
        <h1 class="text-3xl sm:text-4xl font-bold text-gray-900">
          經營者時間銀行
        </h1>
        <p class="mt-4 text-lg text-gray-600 max-w-2xl mx-auto">
          省去摸索、試錯，高效經營你的人生，朝著健康、快樂、富足前進。
        </p>
      </div>

      <!-- Course grid -->
      <div v-if="courses.length > 0">
        <h2 class="text-xl font-semibold text-gray-900 mb-6">
          所有課程
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          <CourseCard
            v-for="course in courses"
            :key="course.id"
            :course="course"
          />
        </div>
      </div>

      <!-- Empty state -->
      <div v-else class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">目前沒有課程</h3>
        <p class="mt-1 text-sm text-gray-500">敬請期待新課程上架！</p>
      </div>
    </div>
  </div>
</template>
