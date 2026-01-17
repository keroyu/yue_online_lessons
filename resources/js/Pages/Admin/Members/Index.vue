<script setup>
import { Link, router, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import MemberDetailModal from '@/Components/MemberDetailModal.vue'
import BatchEmailModal from '@/Components/BatchEmailModal.vue'
import { ref, watch, computed, nextTick } from 'vue'

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

// Flash messages
const page = usePage()

// Member detail modal state
const showMemberModal = ref(false)
const selectedMemberId = ref(null)

// Batch email modal state
const showBatchEmailModal = ref(false)
const batchEmailResult = ref(null)

// Inline editing state
const editingCell = ref(null) // Format: { memberId, field }
const editValue = ref('')
const editErrors = ref({}) // Format: { memberId_field: 'error message' }
const editInputRef = ref(null)

// Copy feedback state
const copiedMemberId = ref(null)

// Selection state
const selectedMemberIds = ref(new Set(props.selectedIds))
const selectAllMatching = ref(false)

// Computed: check if all members on current page are selected
const allOnPageSelected = computed(() => {
  if (!props.members.data?.length) return false
  return props.members.data.every(m => selectedMemberIds.value.has(m.id))
})

// Computed: check if some members on current page are selected
const someOnPageSelected = computed(() => {
  if (!props.members.data?.length) return false
  const selected = props.members.data.filter(m => selectedMemberIds.value.has(m.id))
  return selected.length > 0 && selected.length < props.members.data.length
})

// Computed: total selected count
const selectedCount = computed(() => {
  if (selectAllMatching.value) {
    return props.matchingCount
  }
  return selectedMemberIds.value.size
})

// Computed: is any filter active
const hasActiveFilter = computed(() => {
  return !!(search.value || courseFilter.value)
})

// Toggle single member selection
const toggleMemberSelection = (memberId) => {
  selectAllMatching.value = false
  if (selectedMemberIds.value.has(memberId)) {
    selectedMemberIds.value.delete(memberId)
  } else {
    selectedMemberIds.value.add(memberId)
  }
  // Force reactivity update
  selectedMemberIds.value = new Set(selectedMemberIds.value)
}

// Toggle select all on current page
const toggleSelectAllOnPage = () => {
  selectAllMatching.value = false
  const allSelected = allOnPageSelected.value
  if (allSelected) {
    // Deselect all on page
    props.members.data.forEach(m => selectedMemberIds.value.delete(m.id))
  } else {
    // Select all on page
    props.members.data.forEach(m => selectedMemberIds.value.add(m.id))
  }
  // Force reactivity update
  selectedMemberIds.value = new Set(selectedMemberIds.value)
}

// Select all matching members
const selectAllMatchingMembers = () => {
  selectAllMatching.value = true
  // Clear individual selections when selecting all matching
  selectedMemberIds.value = new Set()
}

// Clear all selections
const clearAllSelections = () => {
  selectAllMatching.value = false
  selectedMemberIds.value = new Set()
}

// Check if a member is selected
const isMemberSelected = (memberId) => {
  if (selectAllMatching.value) return true
  return selectedMemberIds.value.has(memberId)
}

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

// Inline editing functions
const startEditing = async (member, field) => {
  editingCell.value = { memberId: member.id, field }
  editValue.value = member[field] || ''
  // Clear any previous error for this cell
  delete editErrors.value[`${member.id}_${field}`]
  await nextTick()
  editInputRef.value?.focus()
  editInputRef.value?.select()
}

const isEditing = (memberId, field) => {
  return editingCell.value?.memberId === memberId && editingCell.value?.field === field
}

const cancelEditing = () => {
  editingCell.value = null
  editValue.value = ''
}

const saveEdit = (member) => {
  if (!editingCell.value) return

  const { field } = editingCell.value
  const originalValue = member[field] || ''

  // Don't save if value hasn't changed
  if (editValue.value === originalValue) {
    cancelEditing()
    return
  }

  router.patch(`/admin/members/${member.id}`, {
    [field]: editValue.value || null,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      cancelEditing()
      // Clear error if exists
      delete editErrors.value[`${member.id}_${field}`]
    },
    onError: (errors) => {
      // Store error for this specific cell
      if (errors[field]) {
        editErrors.value[`${member.id}_${field}`] = errors[field]
      }
    },
  })
}

const getError = (memberId, field) => {
  return editErrors.value[`${memberId}_${field}`]
}

// Copy to clipboard function
const copyEmail = async (email, memberId) => {
  try {
    await navigator.clipboard.writeText(email)
    copiedMemberId.value = memberId
    setTimeout(() => {
      copiedMemberId.value = null
    }, 2000)
  } catch {
    // Fallback for older browsers
    const textarea = document.createElement('textarea')
    textarea.value = email
    document.body.appendChild(textarea)
    textarea.select()
    document.execCommand('copy')
    document.body.removeChild(textarea)
    copiedMemberId.value = memberId
    setTimeout(() => {
      copiedMemberId.value = null
    }, 2000)
  }
}

// Open member detail modal
const openMemberModal = (member) => {
  selectedMemberId.value = member.id
  showMemberModal.value = true
}

const closeMemberModal = () => {
  showMemberModal.value = false
  selectedMemberId.value = null
}

// Batch email modal functions
const openBatchEmailModal = () => {
  if (selectedCount.value === 0) return
  batchEmailResult.value = null
  showBatchEmailModal.value = true
}

const closeBatchEmailModal = () => {
  showBatchEmailModal.value = false
}

const handleBatchEmailSent = (result) => {
  batchEmailResult.value = result
  // Clear selections after successful send
  clearAllSelections()
  // Auto-hide success message after 5 seconds
  setTimeout(() => {
    batchEmailResult.value = null
  }, 5000)
}

// Get array of selected member IDs for batch email
const selectedMemberIdsArray = computed(() => {
  return Array.from(selectedMemberIds.value)
})
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

      <!-- Success message for batch email -->
      <div v-if="batchEmailResult?.success" class="mt-4 bg-green-50 border border-green-200 rounded-lg p-3 flex items-center justify-between">
        <div class="flex items-center gap-2">
          <svg class="h-5 w-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
          <span class="text-sm text-green-800">{{ batchEmailResult.message }}</span>
        </div>
        <button
          type="button"
          @click="batchEmailResult = null"
          class="text-green-600 hover:text-green-800"
        >
          <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Selection bar -->
      <div v-if="selectedCount > 0" class="mt-4 bg-indigo-50 border border-indigo-200 rounded-lg p-3 flex items-center justify-between">
        <div class="flex items-center gap-4">
          <span class="text-sm text-indigo-800">
            已選取 <strong>{{ selectedCount }}</strong> 位會員
          </span>
          <button
            type="button"
            @click="clearAllSelections"
            class="text-sm text-indigo-600 hover:text-indigo-800 underline"
          >
            清除選取
          </button>
        </div>
        <button
          type="button"
          @click="openBatchEmailModal"
          class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700 transition-colors flex items-center gap-2"
        >
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
          </svg>
          發送郵件
        </button>
      </div>

      <!-- Select all matching banner -->
      <div
        v-if="allOnPageSelected && !selectAllMatching && matchingCount > members.data?.length"
        class="mt-4 bg-yellow-50 border border-yellow-200 rounded-lg p-3 text-center"
      >
        <span class="text-sm text-yellow-800">
          已選取此頁 {{ members.data?.length }} 位會員。
          <button
            type="button"
            @click="selectAllMatchingMembers"
            class="text-yellow-700 hover:text-yellow-900 underline font-medium"
          >
            選取所有符合條件的 {{ matchingCount }} 位會員
          </button>
        </span>
      </div>

      <!-- Select all matching confirmation -->
      <div
        v-if="selectAllMatching"
        class="mt-4 bg-green-50 border border-green-200 rounded-lg p-3 text-center"
      >
        <span class="text-sm text-green-800">
          已選取所有符合條件的 <strong>{{ matchingCount }}</strong> 位會員。
          <button
            type="button"
            @click="clearAllSelections"
            class="text-green-700 hover:text-green-900 underline"
          >
            清除選取
          </button>
        </span>
      </div>

      <!-- Table -->
      <div class="mt-4 flex flex-col">
        <div class="-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
          <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
            <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
              <table class="min-w-full divide-y divide-gray-300">
                <thead class="bg-gray-50">
                  <tr>
                    <!-- Checkbox column header -->
                    <th scope="col" class="relative w-12 px-6 sm:w-16 sm:px-8">
                      <input
                        type="checkbox"
                        :checked="allOnPageSelected && members.data?.length > 0"
                        :indeterminate="someOnPageSelected"
                        @change="toggleSelectAllOnPage"
                        class="absolute left-4 top-1/2 -mt-2 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 sm:left-6"
                      />
                    </th>
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
                  <tr v-for="member in members.data" :key="member.id" :class="{ 'bg-indigo-50': isMemberSelected(member.id) }">
                    <!-- Checkbox column -->
                    <td class="relative w-12 px-6 sm:w-16 sm:px-8">
                      <input
                        type="checkbox"
                        :checked="isMemberSelected(member.id)"
                        @change="toggleMemberSelection(member.id)"
                        class="absolute left-4 top-1/2 -mt-2 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 sm:left-6"
                      />
                    </td>
                    <!-- Email column with inline edit and copy button -->
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 sm:pl-6">
                      <div class="flex items-center gap-2">
                        <!-- Inline edit mode -->
                        <template v-if="isEditing(member.id, 'email')">
                          <div class="flex flex-col">
                            <div class="flex items-center gap-1">
                              <input
                                ref="editInputRef"
                                v-model="editValue"
                                type="email"
                                class="block w-48 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                :class="{ 'border-red-500': getError(member.id, 'email') }"
                                @keyup.enter="saveEdit(member)"
                                @keyup.escape="cancelEditing"
                                @blur="saveEdit(member)"
                              />
                            </div>
                            <span v-if="getError(member.id, 'email')" class="text-xs text-red-600 mt-1">
                              {{ getError(member.id, 'email') }}
                            </span>
                          </div>
                        </template>
                        <!-- Display mode -->
                        <template v-else>
                          <span
                            class="text-sm text-gray-900 cursor-pointer hover:text-indigo-600"
                            @click="startEditing(member, 'email')"
                            title="點擊編輯"
                          >
                            {{ member.email }}
                          </span>
                          <!-- Copy button -->
                          <button
                            type="button"
                            @click.stop="copyEmail(member.email, member.id)"
                            class="text-gray-400 hover:text-gray-600"
                            :title="copiedMemberId === member.id ? '已複製！' : '複製 Email'"
                          >
                            <svg v-if="copiedMemberId === member.id" class="h-4 w-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                          </button>
                        </template>
                        <span v-if="getError(member.id, 'email') && !isEditing(member.id, 'email')" class="text-xs text-red-600">
                          {{ getError(member.id, 'email') }}
                        </span>
                      </div>
                    </td>

                    <!-- Real name column with inline edit -->
                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                      <div class="flex flex-col">
                        <template v-if="isEditing(member.id, 'real_name')">
                          <input
                            ref="editInputRef"
                            v-model="editValue"
                            type="text"
                            class="block w-32 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                            :class="{ 'border-red-500': getError(member.id, 'real_name') }"
                            @keyup.enter="saveEdit(member)"
                            @keyup.escape="cancelEditing"
                            @blur="saveEdit(member)"
                          />
                          <span v-if="getError(member.id, 'real_name')" class="text-xs text-red-600 mt-1">
                            {{ getError(member.id, 'real_name') }}
                          </span>
                        </template>
                        <template v-else>
                          <span
                            class="text-gray-500 cursor-pointer hover:text-indigo-600"
                            @click="startEditing(member, 'real_name')"
                            title="點擊編輯"
                          >
                            {{ member.real_name || '-' }}
                          </span>
                          <span v-if="getError(member.id, 'real_name')" class="text-xs text-red-600">
                            {{ getError(member.id, 'real_name') }}
                          </span>
                        </template>
                      </div>
                    </td>

                    <!-- Phone column with inline edit -->
                    <td class="hidden md:table-cell whitespace-nowrap px-3 py-4 text-sm">
                      <div class="flex flex-col">
                        <template v-if="isEditing(member.id, 'phone')">
                          <input
                            ref="editInputRef"
                            v-model="editValue"
                            type="tel"
                            class="block w-32 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                            :class="{ 'border-red-500': getError(member.id, 'phone') }"
                            @keyup.enter="saveEdit(member)"
                            @keyup.escape="cancelEditing"
                            @blur="saveEdit(member)"
                          />
                          <span v-if="getError(member.id, 'phone')" class="text-xs text-red-600 mt-1">
                            {{ getError(member.id, 'phone') }}
                          </span>
                        </template>
                        <template v-else>
                          <span
                            class="text-gray-500 cursor-pointer hover:text-indigo-600"
                            @click="startEditing(member, 'phone')"
                            title="點擊編輯"
                          >
                            {{ member.phone || '-' }}
                          </span>
                          <span v-if="getError(member.id, 'phone')" class="text-xs text-red-600">
                            {{ getError(member.id, 'phone') }}
                          </span>
                        </template>
                      </div>
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
                        @click="openMemberModal(member)"
                      >
                        查看
                      </button>
                    </td>
                  </tr>
                  <tr v-if="members.data?.length === 0">
                    <td colspan="7" class="px-6 py-12 text-center text-gray-500">
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

    <!-- Member Detail Modal -->
    <MemberDetailModal
      :show="showMemberModal"
      :member-id="selectedMemberId"
      @close="closeMemberModal"
    />

    <!-- Batch Email Modal -->
    <BatchEmailModal
      :show="showBatchEmailModal"
      :selected-count="selectedCount"
      :member-ids="selectedMemberIdsArray"
      :select-all-matching="selectAllMatching"
      :filters="filters"
      @close="closeBatchEmailModal"
      @sent="handleBatchEmailSent"
    />
  </AdminLayout>
</template>
