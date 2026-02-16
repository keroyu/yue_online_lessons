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
        <div class="mt-4 sm:mt-0 flex space-x-3">
          <Link
            :href="`/admin/courses/${course.id}/chapters`"
            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
          >
            編輯章節
          </Link>
          <Link
            :href="`/admin/courses/${course.id}/images`"
            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
          >
            相簿管理
          </Link>
          <Link
            v-if="course.course_type === 'drip'"
            :href="`/admin/courses/${course.id}/subscribers`"
            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
          >
            訂閱者
          </Link>
          <button
            v-if="canPublish"
            type="button"
            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700"
            @click="publish"
          >
            發佈課程
          </button>
          <button
            v-if="canUnpublish"
            type="button"
            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-yellow-600 hover:bg-yellow-700"
            @click="unpublish"
          >
            下架為草稿
          </button>
        </div>
      </div>

      <CourseForm
        :course="course"
        :images="images"
        :available-courses="availableCourses"
        :course-lessons="courseLessons"
        :submit-url="`/admin/courses/${course.id}`"
        method="put"
      />
  </div>
</template>
