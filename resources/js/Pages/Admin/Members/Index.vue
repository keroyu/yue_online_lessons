<script setup>
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { ref, watch, computed } from 'vue'

const props = defineProps({
  members: {
    type: Object,
    required: true,
  },
  filters: {
    type: Object,
    required: true,
  },
  courses: {
    type: Array,
    required: true,
  },
  selectedIds: {
    type: Array,
    default: () => [],
  },
  matchingCount: {
    type: Number,
    default: 0,
  },
})

// Search and filter state
const search = ref(props.filters.search || '')
const courseFilter = ref(props.filters.course_id || '')
const sortField = ref(props.filters.sort || 'created_at')
const sortDirection = ref(props.filters.direction || 'desc')

// Debounced search
let searchTimeout = null
watch(search, (value) => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    applyFilters()
  }, 300)
})

// Apply filters to reload page
const applyFilters = () => {
  router.get('/admin/members', {
    search: search.value || undefined,
    course_id: courseFilter.value || undefined,
    sort: sortField.value,
    direction: sortDirection.value,
  }, {
    preserveState: true,
    replace: true,
  })
}

// Sort by column
const sortBy = (field) => {
  if (sortField.value === field) {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortField.value = field
    sortDirection.value = 'desc'
  }
  applyFilters()
}

// Sort icon helper
const getSortIcon = (field) => {
  if (sortField.value !== field) return ''
  return sortDirection.value === 'asc' ? '↑' : '↓'
}

// Format date for display
const formatDate = (dateString) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('zh-TW')
}

const formatDateTime = (dateString) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleString('zh-TW')
}

// Clear course filter
const clearCourseFilter = () => {
  courseFilter.value = ''
  applyFilters()
}

// Pagination
const goToPage = (page) => {
  router.get('/admin/members', {
    page,
    search: search.value || undefined,
    course_id: courseFilter.value || undefined,
    sort: sortField.value,
    direction: sortDirection.value,
  }, {
    preserveState: true,
  })
}
</script>

<template>
  <AdminLayout>
    <div class="px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
          <h1 class="text-2xl font-semibold text-gray-900">會員管理</h1>
          <p class="mt-2 text-sm text-gray-700">
            管理所有會員資料、檢視課程進度、發送批次郵件。
          </p>
        </div>
      </div>

      <!-- Filters -->
      <div class="mt-6 flex flex-col sm:flex-row gap-4">
        <!-- Search -->
        <div class="flex-1">
          <input
            v-model="search"
            type="text"
            placeholder="搜尋 Email、姓名、暱稱..."
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          />
        </div>

        <!-- Course filter -->
        <div class="flex items-center gap-2">
          <select
            v-model="courseFilter"
            @change="applyFilters"
            class="block rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          >
            <option value="">所有課程</option>
            <option v-for="course in courses" :key="course.id" :value="course.id">
              {{ course.name }}
            </option>
          </select>
          <button
            v-if="courseFilter"
            @click="clearCourseFilter"
            class="text-gray-400 hover:text-gray-600"
            title="清除篩選"
          >
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Table -->
      <div class="mt-8 flex flex-col">
        <div class="-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
          <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
            <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
              <table class="min-w-full divide-y divide-gray-300">
                <thead class="bg-gray-50">
                  <tr>
                    <th
                      scope="col"
                      class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6 cursor-pointer hover:bg-gray-100"
                      @click="sortBy('email')"
                    >
                      Email {{ getSortIcon('email') }}
                    </th>
                    <th
                      scope="col"
                      class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 cursor-pointer hover:bg-gray-100"
                      @click="sortBy('real_name')"
                    >
                      姓名 {{ getSortIcon('real_name') }}
                    </th>
                    <th
                      scope="col"
                      class="hidden md:table-cell px-3 py-3.5 text-left text-sm font-semibold text-gray-900"
                    >
                      電話
                    </th>
                    <th
                      scope="col"
                      class="hidden lg:table-cell px-3 py-3.5 text-left text-sm font-semibold text-gray-900 cursor-pointer hover:bg-gray-100"
                      @click="sortBy('created_at')"
                    >
                      註冊時間 {{ getSortIcon('created_at') }}
                    </th>
                    <th
                      scope="col"
                      class="hidden lg:table-cell px-3 py-3.5 text-left text-sm font-semibold text-gray-900 cursor-pointer hover:bg-gray-100"
                      @click="sortBy('last_login_at')"
                    >
                      最後登入 {{ getSortIcon('last_login_at') }}
                    </th>
                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                      <span class="sr-only">操作</span>
                    </th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                  <tr v-for="member in members.data" :key="member.id">
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 sm:pl-6">
                      <div class="flex items-center">
                        <span class="text-sm text-gray-900">{{ member.email }}</span>
                      </div>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                      {{ member.real_name || '-' }}
                    </td>
                    <td class="hidden md:table-cell whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                      {{ member.phone || '-' }}
                    </td>
                    <td class="hidden lg:table-cell whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                      {{ formatDate(member.created_at) }}
                    </td>
                    <td class="hidden lg:table-cell whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                      {{ formatDateTime(member.last_login_at) }}
                    </td>
                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                      <button
                        type="button"
                        class="text-indigo-600 hover:text-indigo-900"
                      >
                        查看
                      </button>
                    </td>
                  </tr>
                  <tr v-if="members.data?.length === 0">
                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                      {{ search || courseFilter ? '沒有符合條件的會員' : '尚無會員資料' }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="members.last_page > 1" class="mt-4 flex items-center justify-between">
        <div class="text-sm text-gray-700">
          顯示第 {{ (members.current_page - 1) * members.per_page + 1 }} - {{ Math.min(members.current_page * members.per_page, members.total) }} 筆，共 {{ members.total }} 筆
        </div>
        <nav class="flex items-center space-x-2">
          <button
            @click="goToPage(members.current_page - 1)"
            :disabled="members.current_page === 1"
            class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            上一頁
          </button>
          <span class="text-sm text-gray-700">
            {{ members.current_page }} / {{ members.last_page }}
          </span>
          <button
            @click="goToPage(members.current_page + 1)"
            :disabled="members.current_page === members.last_page"
            class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            下一頁
          </button>
        </nav>
      </div>
    </div>
  </AdminLayout>
</template>
