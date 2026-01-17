<script setup>
import { Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import ChapterList from '@/Components/Admin/ChapterList.vue'

defineProps({
  course: {
    type: Object,
    required: true,
  },
  chapters: {
    type: Array,
    required: true,
  },
  standaloneLessons: {
    type: Array,
    default: () => [],
  },
})
</script>

<template>
  <AdminLayout>
    <div class="px-4 sm:px-6 lg:px-8">
      <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
          <nav class="flex" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-4">
              <li>
                <Link href="/admin/courses" class="text-sm font-medium text-gray-500 hover:text-gray-700">
                  課程管理
                </Link>
              </li>
              <li>
                <div class="flex items-center">
                  <svg class="flex-shrink-0 h-5 w-5 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                  </svg>
                  <span class="ml-4 text-sm font-medium text-gray-500">{{ course.name }}</span>
                </div>
              </li>
            </ol>
          </nav>
          <h1 class="mt-2 text-2xl font-semibold text-gray-900">章節編輯</h1>
          <p class="mt-1 text-sm text-gray-500">
            拖曳調整章節和小節順序，點擊編輯內容
          </p>
        </div>
        <div class="mt-4 sm:mt-0 flex space-x-3">
          <Link
            :href="`/admin/courses/${course.id}/edit`"
            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
          >
            課程設定
          </Link>
          <Link
            :href="`/admin/courses/${course.id}/images`"
            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
          >
            相簿管理
          </Link>
        </div>
      </div>

      <ChapterList
        :course-id="course.id"
        :chapters="chapters"
        :standalone-lessons="standaloneLessons"
      />
    </div>
  </AdminLayout>
</template>
