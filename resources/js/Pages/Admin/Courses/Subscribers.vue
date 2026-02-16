<script setup>
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { ref } from 'vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  course: {
    type: Object,
    required: true,
  },
  subscribers: {
    type: Object,
    required: true,
  },
  stats: {
    type: Object,
    required: true,
  },
  filters: {
    type: Object,
    required: true,
  },
})

const statusFilter = ref(props.filters.status || '')

const statusLabels = {
  active: '發信中',
  converted: '已轉換',
  completed: '已完成',
  unsubscribed: '已退訂',
}

const statusClasses = {
  active: 'bg-green-100 text-green-800',
  converted: 'bg-blue-100 text-blue-800',
  completed: 'bg-gray-100 text-gray-800',
  unsubscribed: 'bg-red-100 text-red-800',
}

const applyFilter = () => {
  router.get(`/admin/courses/${props.course.id}/subscribers`, {
    status: statusFilter.value || undefined,
  }, {
    preserveState: true,
    replace: true,
  })
}

const clearFilter = () => {
  statusFilter.value = ''
  applyFilter()
}

const formatDate = (dateString) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('zh-TW')
}

const formatDateTime = (dateString) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleString('zh-TW')
}

const goToPage = (page) => {
  router.get(`/admin/courses/${props.course.id}/subscribers`, {
    page,
    status: statusFilter.value || undefined,
  }, {
    preserveState: true,
  })
}
</script>

<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="mb-8">
      <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">
        <Link href="/admin/courses" class="hover:text-gray-700">課程管理</Link>
        <span>/</span>
        <Link :href="`/admin/courses/${course.id}/edit`" class="hover:text-gray-700">{{ course.name }}</Link>
        <span>/</span>
        <span class="text-gray-900">訂閱者</span>
      </div>
      <h1 class="text-2xl font-semibold text-gray-900">訂閱者清單</h1>
      <p class="mt-1 text-sm text-gray-600">{{ course.name }} - 共 {{ course.total_lessons }} 個小節</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-4 mb-6">
      <div class="bg-white rounded-lg shadow-sm p-4">
        <p class="text-sm text-gray-500">全部</p>
        <p class="text-2xl font-semibold text-gray-900">{{ stats.total }}</p>
      </div>
      <div class="bg-white rounded-lg shadow-sm p-4">
        <p class="text-sm text-green-600">發信中</p>
        <p class="text-2xl font-semibold text-green-700">{{ stats.active }}</p>
      </div>
      <div class="bg-white rounded-lg shadow-sm p-4">
        <p class="text-sm text-blue-600">已轉換</p>
        <p class="text-2xl font-semibold text-blue-700">{{ stats.converted }}</p>
      </div>
      <div class="bg-white rounded-lg shadow-sm p-4">
        <p class="text-sm text-gray-500">已完成</p>
        <p class="text-2xl font-semibold text-gray-700">{{ stats.completed }}</p>
      </div>
      <div class="bg-white rounded-lg shadow-sm p-4">
        <p class="text-sm text-red-600">已退訂</p>
        <p class="text-2xl font-semibold text-red-700">{{ stats.unsubscribed }}</p>
      </div>
    </div>

    <!-- Filter -->
    <div class="mb-4 flex items-center gap-3">
      <select
        v-model="statusFilter"
        @change="applyFilter"
        class="block rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
      >
        <option value="">所有狀態</option>
        <option value="active">發信中</option>
        <option value="converted">已轉換</option>
        <option value="completed">已完成</option>
        <option value="unsubscribed">已退訂</option>
      </select>
      <button
        v-if="statusFilter"
        @click="clearFilter"
        class="text-gray-400 hover:text-gray-600"
        title="清除篩選"
      >
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <!-- Table -->
    <div class="flex flex-col">
      <div class="-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
        <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
          <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
            <table class="min-w-full divide-y divide-gray-300">
              <thead class="bg-gray-50">
                <tr>
                  <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Email</th>
                  <th scope="col" class="hidden sm:table-cell px-3 py-3.5 text-left text-sm font-semibold text-gray-900">暱稱</th>
                  <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">狀態</th>
                  <th scope="col" class="hidden md:table-cell px-3 py-3.5 text-left text-sm font-semibold text-gray-900">進度</th>
                  <th scope="col" class="hidden lg:table-cell px-3 py-3.5 text-left text-sm font-semibold text-gray-900">訂閱時間</th>
                  <th scope="col" class="hidden lg:table-cell px-3 py-3.5 text-left text-sm font-semibold text-gray-900">狀態變更</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200 bg-white">
                <tr v-for="sub in subscribers.data" :key="sub.id">
                  <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm text-gray-900 sm:pl-6">
                    {{ sub.user?.email || '-' }}
                  </td>
                  <td class="hidden sm:table-cell whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                    {{ sub.user?.nickname || '-' }}
                  </td>
                  <td class="whitespace-nowrap px-3 py-4 text-sm">
                    <span
                      class="inline-flex rounded-full px-2 py-1 text-xs font-semibold"
                      :class="statusClasses[sub.status] || 'bg-gray-100 text-gray-800'"
                    >
                      {{ statusLabels[sub.status] || sub.status }}
                    </span>
                  </td>
                  <td class="hidden md:table-cell whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                    {{ sub.emails_sent }} / {{ course.total_lessons }}
                  </td>
                  <td class="hidden lg:table-cell whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                    {{ formatDate(sub.subscribed_at) }}
                  </td>
                  <td class="hidden lg:table-cell whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                    {{ formatDateTime(sub.status_changed_at) }}
                  </td>
                </tr>
                <tr v-if="subscribers.data?.length === 0">
                  <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                    {{ statusFilter ? '沒有符合條件的訂閱者' : '尚無訂閱者' }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="subscribers.last_page > 1" class="mt-4 flex items-center justify-between">
      <div class="text-sm text-gray-700">
        顯示第 {{ (subscribers.current_page - 1) * subscribers.per_page + 1 }} - {{ Math.min(subscribers.current_page * subscribers.per_page, subscribers.total) }} 筆，共 {{ subscribers.total }} 筆
      </div>
      <nav class="flex items-center space-x-2">
        <button
          @click="goToPage(subscribers.current_page - 1)"
          :disabled="subscribers.current_page === 1"
          class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          上一頁
        </button>
        <span class="text-sm text-gray-700">
          {{ subscribers.current_page }} / {{ subscribers.last_page }}
        </span>
        <button
          @click="goToPage(subscribers.current_page + 1)"
          :disabled="subscribers.current_page === subscribers.last_page"
          class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          下一頁
        </button>
      </nav>
    </div>
  </div>
</template>
