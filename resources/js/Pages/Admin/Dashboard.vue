<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

defineOptions({
  layout: AdminLayout
})

defineProps({
  stats: {
    type: Object,
    required: true,
  },
  recentCourses: {
    type: Array,
    required: true,
  },
})

const getStatusBadgeClass = (status) => {
  switch (status) {
    case 'draft':
      return 'bg-gray-100 text-gray-800'
    case 'preorder':
      return 'bg-yellow-100 text-yellow-800'
    case 'selling':
      return 'bg-green-100 text-green-800'
    default:
      return 'bg-gray-100 text-gray-800'
  }
}

const getStatusLabel = (status) => {
  switch (status) {
    case 'draft':
      return '草稿'
    case 'preorder':
      return '預購中'
    case 'selling':
      return '熱賣中'
    default:
      return status
  }
}
</script>

<template>
  <Head title="Dashboard" />

  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-semibold text-gray-900">Dashboard</h1>

    <!-- Stats cards -->
    <div class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
              </svg>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">課程總數</dt>
                <dd class="text-lg font-semibold text-gray-900">{{ stats.total_courses }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <svg class="h-6 w-6 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">已發佈</dt>
                <dd class="text-lg font-semibold text-gray-900">{{ stats.published_courses }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <svg class="h-6 w-6 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">草稿</dt>
                <dd class="text-lg font-semibold text-gray-900">{{ stats.draft_courses }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-white overflow-hidden shadow rounded-lg">
        <div class="p-5">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <svg class="h-6 w-6 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
            </div>
            <div class="ml-5 w-0 flex-1">
              <dl>
                <dt class="text-sm font-medium text-gray-500 truncate">會員數</dt>
                <dd class="text-lg font-semibold text-gray-900">{{ stats.total_users }}</dd>
              </dl>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent courses -->
    <div class="mt-8">
      <div class="flex items-center justify-between">
        <h2 class="text-lg font-medium text-gray-900">最近新增課程</h2>
        <Link
          href="/admin/courses"
          class="text-sm font-medium text-indigo-600 hover:text-indigo-500"
        >
          查看全部
        </Link>
      </div>
      <div class="mt-4 bg-white shadow overflow-hidden sm:rounded-md">
        <ul role="list" class="divide-y divide-gray-200">
          <li v-for="course in recentCourses" :key="course.id">
            <div class="px-4 py-4 sm:px-6">
              <div class="flex items-center justify-between">
                <div class="flex items-center">
                  <p class="text-sm font-medium text-indigo-600 truncate">{{ course.name }}</p>
                  <span
                    class="ml-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                    :class="getStatusBadgeClass(course.status)"
                  >
                    {{ getStatusLabel(course.status) }}
                  </span>
                  <span
                    v-if="course.is_published"
                    class="ml-1 px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800"
                  >
                    已上架
                  </span>
                </div>
                <div class="text-sm text-gray-500">
                  {{ new Date(course.created_at).toLocaleDateString('zh-TW') }}
                </div>
              </div>
            </div>
          </li>
          <li v-if="recentCourses.length === 0">
            <div class="px-4 py-8 text-center text-gray-500">
              目前沒有課程
            </div>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>
