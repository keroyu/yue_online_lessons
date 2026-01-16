<script setup>
defineProps({
  course: {
    type: Object,
    required: true,
  },
})

const formatDate = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('zh-TW', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
}
</script>

<template>
  <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
    <!-- Thumbnail -->
    <div class="relative">
      <img
        v-if="course.thumbnail"
        :src="course.thumbnail"
        :alt="course.name"
        class="w-full h-40 sm:h-48 object-cover"
      />
      <div
        v-else
        class="w-full h-40 sm:h-48 bg-gray-200 flex items-center justify-center"
      >
        <span class="text-gray-400 text-sm">No Image</span>
      </div>

      <!-- Progress Badge -->
      <div class="absolute top-2 right-2 bg-black bg-opacity-70 text-white text-xs px-2 py-1 rounded">
        {{ course.progress_percent }}% 完成
      </div>
    </div>

    <!-- Content -->
    <div class="p-4">
      <h3 class="font-semibold text-gray-900 mb-1 line-clamp-2">
        {{ course.name }}
      </h3>
      <p class="text-sm text-gray-500 mb-3">
        {{ course.instructor_name }}
      </p>

      <!-- Progress Bar -->
      <div class="mb-3">
        <div class="w-full bg-gray-200 rounded-full h-2">
          <div
            class="bg-indigo-600 h-2 rounded-full transition-all duration-300"
            :style="{ width: `${course.progress_percent}%` }"
          ></div>
        </div>
      </div>

      <!-- Footer -->
      <div class="flex items-center justify-between text-xs text-gray-400">
        <span>購買日期：{{ formatDate(course.purchased_at) }}</span>
      </div>

      <!-- Action Button (placeholder for future classroom link) -->
      <button
        class="mt-3 w-full bg-indigo-600 text-white py-2 px-4 rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium"
        disabled
      >
        開始上課（即將推出）
      </button>
    </div>
  </div>
</template>
