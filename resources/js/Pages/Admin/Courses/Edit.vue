<script setup>
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import CourseForm from '@/Components/Admin/CourseForm.vue'
import { computed } from 'vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  course: {
    type: Object,
    required: true,
  },
  images: {
    type: Array,
    default: () => [],
  },
  availableCourses: {
    type: Array,
    default: () => [],
  },
  courseLessons: {
    type: Array,
    default: () => [],
  },
  gatewayConfigured: {
    type: Object,
    default: () => ({ payuni: true, newebpay: true }),
  },
})

const statusBadge = computed(() => {
  const badges = {
    draft: { text: '草稿', class: 'bg-gray-100 text-gray-800' },
    preorder: { text: '預購中', class: 'bg-yellow-100 text-yellow-800' },
    selling: { text: '熱賣中', class: 'bg-green-100 text-green-800' },
  }
  return badges[props.course.status] || badges.draft
})

const canPublish = computed(() => props.course.status === 'draft')
const canUnpublish = computed(() => ['preorder', 'selling'].includes(props.course.status))

const publish = () => {
  if (confirm('確定要發佈此課程嗎？')) {
    router.post(`/admin/courses/${props.course.id}/publish`)
  }
}

const unpublish = () => {
  if (confirm('確定要下架此課程為草稿嗎？')) {
    router.post(`/admin/courses/${props.course.id}/unpublish`)
  }
}
</script>

<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
          <div class="flex items-center space-x-3">
            <h1 class="text-2xl font-semibold text-gray-900">編輯課程</h1>
            <span
              class="inline-flex rounded-full px-3 py-1 text-sm font-semibold"
              :class="statusBadge.class"
            >
              {{ statusBadge.text }}
            </span>
          </div>
          <p class="mt-2 text-sm text-gray-700">
            {{ course.name }}
          </p>
        </div>
        <div class="mt-4 sm:mt-0 flex items-center gap-1">
          <!-- Nav links -->
          <a
            :href="`/member/classroom/${course.id}`"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-md text-sm font-medium text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 transition-colors"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
            </svg>
            預覽教室
          </a>
          <Link
            :href="`/admin/courses/${course.id}/chapters`"
            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-md text-sm font-medium text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 transition-colors"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h7" />
            </svg>
            編輯章節
          </Link>
          <Link
            :href="`/admin/courses/${course.id}/images`"
            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-md text-sm font-medium text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 transition-colors"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            相簿管理
          </Link>
          <Link
            v-if="course.delivery_mode === 'drip'"
            :href="`/admin/courses/${course.id}/subscribers`"
            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-md text-sm font-medium text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 transition-colors"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            訂閱者
          </Link>

          <!-- Divider -->
          <div class="mx-2 h-5 w-px bg-gray-200" />

          <!-- Action buttons -->
          <button
            v-if="canPublish"
            type="button"
            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-md text-sm font-medium text-white bg-green-600 hover:bg-green-700 shadow-sm transition-colors"
            @click="publish"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            發佈課程
          </button>
          <button
            v-if="canUnpublish"
            type="button"
            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-md text-sm font-medium text-white bg-amber-600 hover:bg-amber-700 shadow-sm transition-colors"
            @click="unpublish"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
            </svg>
            下架為草稿
          </button>
        </div>
      </div>

      <CourseForm
        :course="course"
        :images="images"
        :available-courses="availableCourses"
        :course-lessons="courseLessons"
        :gateway-configured="gatewayConfigured"
        :submit-url="`/admin/courses/${course.id}`"
        method="put"
      />
  </div>
</template>
