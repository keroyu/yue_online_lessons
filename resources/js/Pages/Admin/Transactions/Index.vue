<script setup>
import { Link, router, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import RevenueChart from '@/Components/Admin/RevenueChart.vue'
import { ref, computed, watch } from 'vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  transactions: {
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
  matchingCount: {
    type: Number,
    default: 0,
  },
  chartData: {
    type: Object,
    required: true,
  },
  chartFilters: {
    type: Object,
    required: true,
  },
})

const page = usePage()

// Flash messages from Inertia session
const flash = computed(() => page.props.flash)

// Filter state
const search = ref(props.filters.search || '')
const statusFilter = ref(props.filters.status || '')
const typeFilter = ref(props.filters.type || '')
const courseFilter = ref(props.filters.course_id || '')

// Selection state
const selectedIds = ref(new Set())
const selectAllMatching = ref(false)

// Manual create modal state
const showCreateModal = ref(false)
const createForm = ref({ user_email: '', user_id: null, course_id: '', type: 'gift' })
const createErrors = ref({})
const createSubmitting = ref(false)
const userSearchResults = ref([])
const userSearchLoading = ref(false)

// Computed: whether all records on the current page are selected
const allOnPageSelected = computed(() => {
  if (!props.transactions.data?.length) return false
  return props.transactions.data.every(t => selectedIds.value.has(t.id))
})

const someOnPageSelected = computed(() => {
  if (!props.transactions.data?.length) return false
  const count = props.transactions.data.filter(t => selectedIds.value.has(t.id)).length
  return count > 0 && count < props.transactions.data.length
})

const selectedCount = computed(() => {
  if (selectAllMatching.value) return props.matchingCount
  return selectedIds.value.size
})

const isSelected = (id) => {
  if (selectAllMatching.value) return true
  return selectedIds.value.has(id)
}

const toggleSelection = (id) => {
  selectAllMatching.value = false
  if (selectedIds.value.has(id)) {
    selectedIds.value.delete(id)
  } else {
    selectedIds.value.add(id)
  }
  selectedIds.value = new Set(selectedIds.value)
}

const toggleSelectAllOnPage = () => {
  selectAllMatching.value = false
  if (allOnPageSelected.value) {
    props.transactions.data.forEach(t => selectedIds.value.delete(t.id))
  } else {
    props.transactions.data.forEach(t => selectedIds.value.add(t.id))
  }
  selectedIds.value = new Set(selectedIds.value)
}

const selectAllMatchingTransactions = () => {
  selectAllMatching.value = true
  selectedIds.value = new Set()
}

const clearAllSelections = () => {
  selectAllMatching.value = false
  selectedIds.value = new Set()
}

// Debounced search
let searchTimeout = null
watch(search, () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => applyFilters(), 300)
})

const applyFilters = () => {
  router.get('/admin/transactions', {
    search: search.value || undefined,
    status: statusFilter.value || undefined,
    type: typeFilter.value || undefined,
    course_id: courseFilter.value || undefined,
  }, {
    preserveState: true,
    replace: true,
  })
}

const goToPage = (pageNum) => {
  router.get('/admin/transactions', {
    page: pageNum,
    search: search.value || undefined,
    status: statusFilter.value || undefined,
    type: typeFilter.value || undefined,
    course_id: courseFilter.value || undefined,
  }, { preserveState: true })
}

// CSV export
const exportCsv = () => {
  if (selectedCount.value === 0) return

  const params = new URLSearchParams()

  if (selectAllMatching.value) {
    params.set('select_all', 'true')
    if (search.value)        params.set('search', search.value)
    if (statusFilter.value)  params.set('status', statusFilter.value)
    if (typeFilter.value)    params.set('type', typeFilter.value)
    if (courseFilter.value)  params.set('course_id', courseFilter.value)
  } else {
    selectedIds.value.forEach(id => params.append('ids[]', id))
  }

  window.location.href = '/admin/transactions/export?' + params.toString()
}

// Format helpers
const formatAmount = (currency, amount) => {
  if (amount === null || amount === undefined) return '-'
  return `${currency} ${Number(amount).toFixed(2)}`
}

const formatDate = (dateString) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleString('zh-TW', { timeZone: 'Asia/Taipei' })
}

const statusLabel = (status, type) => {
  if (status === 'refunded') return type === 'paid' ? '已退款' : '已撤銷'
  return type === 'paid' ? '已付款' : '有效'
}

const statusClass = (status, type) => {
  if (status === 'refunded') {
    return 'inline-flex px-2 py-0.5 text-xs font-medium rounded-full bg-red-100 text-red-800'
  }
  return type === 'paid'
    ? 'inline-flex px-2 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-800'
    : 'inline-flex px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 text-blue-800'
}

// Badge copy-to-clipboard
const copiedId = ref(null)

const badgeConfig = (transaction) => {
  // Data-driven: use whatever order id is available rather than depend on source field
  // (source can be null/inconsistent on legacy webhook records).
  if (transaction.portaly_order_id) {
    return { label: 'Portaly', orderId: transaction.portaly_order_id, classes: 'bg-slate-100 text-slate-700' }
  }
  const merchantOrderNo = transaction.order?.merchant_order_no
  const gateway         = transaction.order?.payment_gateway
  if (merchantOrderNo && gateway === 'payuni') {
    return { label: 'PayUni', orderId: merchantOrderNo, classes: 'bg-indigo-100 text-indigo-700' }
  }
  if (merchantOrderNo && gateway === 'newebpay') {
    return { label: 'NewebPay', orderId: merchantOrderNo, classes: 'bg-blue-100 text-blue-700' }
  }
  // Legacy PayUni single-course purchase: no Order, only payuni_trade_no on Purchase
  if (transaction.payuni_trade_no) {
    return { label: 'PayUni', orderId: transaction.payuni_trade_no, classes: 'bg-indigo-100 text-indigo-700' }
  }
  return null
}

const copyOrderId = (orderId) => {
  if (!orderId) return
  navigator.clipboard?.writeText(orderId).then(() => {
    copiedId.value = orderId
    setTimeout(() => { copiedId.value = null }, 1500)
  }).catch(() => {})
}

const handleRefund = (transaction) => {
  if (!window.confirm('確認將此交易標記為退款？退款後該會員的課程存取將被撤銷。')) return
  router.patch(`/admin/transactions/${transaction.id}/refund`, {}, { preserveScroll: true })
}

// Chart range helpers
const changeChartRange = (range) => {
  const params = new URLSearchParams(window.location.search)
  params.set('chart_range', range)
  params.delete('chart_start')
  params.delete('chart_end')
  router.visit(`/admin/transactions?${params}`, {
    only: ['chartData', 'chartFilters'],
    preserveState: true,
    preserveScroll: true,
  })
}

const changeCustomRange = (start, end) => {
  const params = new URLSearchParams(window.location.search)
  params.set('chart_range', 'custom')
  params.set('chart_start', start)
  params.set('chart_end', end)
  router.visit(`/admin/transactions?${params}`, {
    only: ['chartData', 'chartFilters'],
    preserveState: true,
    preserveScroll: true,
  })
}

// Manual create modal
const openCreateModal = () => {
  createForm.value = { user_email: '', user_id: null, course_id: '', type: 'gift' }
  createErrors.value = {}
  userSearchResults.value = []
  showCreateModal.value = true
}

const closeCreateModal = () => {
  showCreateModal.value = false
}

let userSearchTimeout = null
const searchUser = (email) => {
  createForm.value.user_email = email
  createForm.value.user_id = null
  clearTimeout(userSearchTimeout)
  if (!email || email.length < 2) {
    userSearchResults.value = []
    return
  }
  userSearchLoading.value = true
  userSearchTimeout = setTimeout(async () => {
    try {
      const res = await window.axios.get('/admin/members', {
        params: { search: email, per_page: 10 },
        headers: { 'X-Inertia': false, Accept: 'application/json' },
      })
      userSearchResults.value = res.data?.members?.data ?? []
    } catch {
      userSearchResults.value = []
    } finally {
      userSearchLoading.value = false
    }
  }, 300)
}

const selectUser = (user) => {
  createForm.value.user_email = user.email
  createForm.value.user_id = user.id
  userSearchResults.value = []
}

const submitCreate = () => {
  createErrors.value = {}
  if (!createForm.value.user_id) {
    createErrors.value.user_id = '請從搜尋結果中選擇會員'
    return
  }
  if (!createForm.value.course_id) {
    createErrors.value.course_id = '請選擇課程'
    return
  }
  createSubmitting.value = true
  router.post('/admin/transactions', {
    user_id:   createForm.value.user_id,
    course_id: parseInt(createForm.value.course_id),
    type:      createForm.value.type,
  }, {
    onSuccess: () => {
      closeCreateModal()
      createSubmitting.value = false
    },
    onError: (errors) => {
      createErrors.value = errors
      createSubmitting.value = false
    },
  })
}
</script>

<template>
  <div class="px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-semibold text-gray-900">交易紀錄</h1>
        <p class="mt-2 text-sm text-gray-700">檢視所有交易，手動新增指派或贈送，標記退款。</p>
      </div>
      <div class="mt-4 sm:mt-0">
        <button
          type="button"
          @click="openCreateModal"
          class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700 transition-colors cursor-pointer"
        >
          手動新增
        </button>
      </div>
    </div>

    <!-- Flash banners -->
    <div v-if="flash?.success" class="mt-4 bg-green-50 border border-green-200 rounded-lg p-3 flex items-center justify-between">
      <span class="text-sm text-green-800">{{ flash.success }}</span>
      <button type="button" @click="page.props.flash = {}" class="text-green-600 hover:text-green-800">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
      </button>
    </div>
    <div v-if="flash?.error" class="mt-4 bg-red-50 border border-red-200 rounded-lg p-3 flex items-center justify-between">
      <span class="text-sm text-red-800">{{ flash.error }}</span>
      <button type="button" @click="page.props.flash = {}" class="text-red-600 hover:text-red-800">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
      </button>
    </div>

    <!-- Revenue Chart -->
    <div class="mt-6">
      <RevenueChart
        :chartData="chartData"
        :chartFilters="chartFilters"
        @change-range="changeChartRange"
        @change-custom="changeCustomRange"
      />
    </div>

    <!-- Filters -->
    <div class="mt-6 flex flex-col sm:flex-row gap-3 flex-wrap">
      <!-- Search -->
      <div class="flex-1 min-w-[180px]">
        <input
          v-model="search"
          type="text"
          placeholder="搜尋 Email 或訂單編號..."
          class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
        />
      </div>
      <!-- Status filter -->
      <select
        v-model="statusFilter"
        @change="applyFilters"
        class="block rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
      >
        <option value="">所有狀態</option>
        <option value="paid">已付款</option>
        <option value="refunded">已退款</option>
      </select>
      <!-- Type filter -->
      <select
        v-model="typeFilter"
        @change="applyFilters"
        class="block rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
      >
        <option value="">所有類型</option>
        <option value="paid">已付款</option>
        <option value="system_assigned">系統指派</option>
        <option value="gift">贈送</option>
      </select>
      <!-- Course filter -->
      <select
        v-model="courseFilter"
        @change="applyFilters"
        class="block rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
      >
        <option value="">所有課程</option>
        <option v-for="course in courses" :key="course.id" :value="course.id">{{ course.name }}</option>
      </select>
    </div>

    <!-- Selection bar -->
    <div v-if="selectedCount > 0" class="mt-4 bg-indigo-50 border border-indigo-200 rounded-lg p-3 flex items-center justify-between">
      <div class="flex items-center gap-4">
        <span class="text-sm text-indigo-800">已選取 <strong>{{ selectedCount }}</strong> 筆交易</span>
        <button type="button" @click="clearAllSelections" class="text-sm text-indigo-600 hover:text-indigo-800 underline">清除選取</button>
      </div>
      <button
        type="button"
        @click="exportCsv"
        :disabled="selectedCount === 0"
        class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-md hover:bg-green-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
      >
        匯出 CSV
      </button>
    </div>

    <!-- Select all matching banner -->
    <div
      v-if="allOnPageSelected && !selectAllMatching && matchingCount > transactions.data?.length"
      class="mt-4 bg-yellow-50 border border-yellow-200 rounded-lg p-3 text-center"
    >
      <span class="text-sm text-yellow-800">
        已選取此頁 {{ transactions.data?.length }} 筆交易。
        <button type="button" @click="selectAllMatchingTransactions" class="text-yellow-700 hover:text-yellow-900 underline font-medium">
          選取所有符合條件的 {{ matchingCount }} 筆交易
        </button>
      </span>
    </div>

    <!-- Select all matching confirmation -->
    <div v-if="selectAllMatching" class="mt-4 bg-green-50 border border-green-200 rounded-lg p-3 text-center">
      <span class="text-sm text-green-800">
        已選取所有符合條件的 <strong>{{ matchingCount }}</strong> 筆交易。
        <button type="button" @click="clearAllSelections" class="text-green-700 hover:text-green-900 underline">清除選取</button>
      </span>
    </div>

    <!-- Table with horizontal scroll for RWD -->
    <div class="mt-4 flex flex-col">
      <div class="-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
        <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
          <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
            <table class="min-w-full divide-y divide-gray-300">
              <thead class="bg-gray-50">
                <tr>
                  <!-- Checkbox column -->
                  <th scope="col" class="relative w-12 px-6 sm:w-16 sm:px-8">
                    <input
                      type="checkbox"
                      :checked="allOnPageSelected && transactions.data?.length > 0"
                      :indeterminate="someOnPageSelected"
                      @change="toggleSelectAllOnPage"
                      class="absolute left-4 top-1/2 -mt-2 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 sm:left-6"
                    />
                  </th>
                  <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">訂單 ID</th>
                  <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">購買者</th>
                  <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">課程</th>
                  <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">金額</th>
                  <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">狀態</th>
                  <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">類型</th>
                  <th scope="col" class="hidden lg:table-cell px-3 py-3.5 text-left text-sm font-semibold text-gray-900">購買時間</th>
                  <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6"><span class="sr-only">操作</span></th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200 bg-white">
                <tr
                  v-for="transaction in transactions.data"
                  :key="transaction.id"
                  :class="{ 'bg-indigo-50': isSelected(transaction.id) }"
                >
                  <!-- Checkbox -->
                  <td class="relative w-12 px-6 sm:w-16 sm:px-8">
                    <input
                      type="checkbox"
                      :checked="isSelected(transaction.id)"
                      @change="toggleSelection(transaction.id)"
                      class="absolute left-4 top-1/2 -mt-2 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 sm:left-6"
                    />
                  </td>
                  <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6">
                    {{ transaction.id }}
                    <template v-if="badgeConfig(transaction)">
                      <button
                        type="button"
                        :title="badgeConfig(transaction).orderId"
                        :class="['mt-1 inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium cursor-pointer select-none', badgeConfig(transaction).classes]"
                        @click="copyOrderId(badgeConfig(transaction).orderId)"
                      >
                        <template v-if="copiedId === badgeConfig(transaction).orderId">已複製 ✓</template>
                        <template v-else>[{{ badgeConfig(transaction).label }}]</template>
                      </button>
                    </template>
                    <span v-else class="mt-1 block text-xs text-gray-400">—</span>
                  </td>
                  <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900">
                    <div>{{ transaction.user?.real_name || transaction.user?.nickname || '-' }}</div>
                    <div class="text-xs text-gray-500">{{ transaction.buyer_email || transaction.user?.email || '-' }}</div>
                  </td>
                  <td class="px-3 py-4 text-sm text-gray-700 max-w-[200px]">
                    <div>{{ transaction.course?.name || '-' }}</div>
                    <template v-if="transaction.course && transaction.progress_total > 0">
                      <div class="mt-1 flex items-center gap-1.5">
                        <div class="flex-1 h-1.5 bg-gray-200 rounded-full overflow-hidden">
                          <div
                            class="h-full bg-indigo-500 rounded-full"
                            :style="{ width: Math.round(transaction.progress_completed / transaction.progress_total * 100) + '%' }"
                          />
                        </div>
                        <span class="text-xs text-gray-500 whitespace-nowrap">{{ transaction.progress_completed }}/{{ transaction.progress_total }} 課</span>
                      </div>
                    </template>
                    <template v-else-if="transaction.course && transaction.progress_total === 0">
                      <div class="mt-1 text-xs text-gray-400">（無課程內容）</div>
                    </template>
                  </td>
                  <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-700">
                    {{ formatAmount(transaction.currency, transaction.amount) }}
                  </td>
                  <td class="whitespace-nowrap px-3 py-4 text-sm">
                    <span :class="statusClass(transaction.status, transaction.type)">{{ statusLabel(transaction.status, transaction.type) }}</span>
                  </td>
                  <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-700">
                    {{ transaction.type_label || transaction.type }}
                  </td>
                  <td class="hidden lg:table-cell whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                    {{ formatDate(transaction.created_at) }}
                  </td>
                  <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                    <button
                      v-if="transaction.status === 'paid'"
                      type="button"
                      class="text-red-600 hover:text-red-900 mr-3 cursor-pointer"
                      @click="handleRefund(transaction)"
                    >
                      標記退款
                    </button>
                    <Link
                      :href="`/admin/transactions/${transaction.id}`"
                      class="text-indigo-600 hover:text-indigo-900"
                    >
                      查看
                    </Link>
                  </td>
                </tr>

                <!-- Empty state -->
                <tr v-if="transactions.data?.length === 0">
                  <td colspan="9" class="px-6 py-16 text-center">
                    <p class="text-gray-500 text-sm">目前沒有符合條件的交易紀錄</p>
                    <p v-if="filters.search || filters.status || filters.type || filters.course_id" class="mt-1 text-xs text-gray-400">
                      請嘗試調整篩選條件
                    </p>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="transactions.last_page > 1" class="mt-4 flex items-center justify-between">
      <div class="text-sm text-gray-700">
        顯示第 {{ (transactions.current_page - 1) * transactions.per_page + 1 }} - {{ Math.min(transactions.current_page * transactions.per_page, transactions.total) }} 筆，共 {{ transactions.total }} 筆
      </div>
      <nav class="flex items-center space-x-2">
        <button
          @click="goToPage(transactions.current_page - 1)"
          :disabled="transactions.current_page === 1"
          class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          上一頁
        </button>
        <span class="text-sm text-gray-700">{{ transactions.current_page }} / {{ transactions.last_page }}</span>
        <button
          @click="goToPage(transactions.current_page + 1)"
          :disabled="transactions.current_page === transactions.last_page"
          class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          下一頁
        </button>
      </nav>
    </div>

  </div>

  <!-- Manual Create Modal -->
  <Teleport to="body">
    <Transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition ease-in duration-150"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div v-if="showCreateModal" class="fixed inset-0 z-50 overflow-y-auto" @click.self="closeCreateModal">
        <div class="fixed inset-0 bg-black/50" aria-hidden="true" />
        <div class="flex min-h-full items-center justify-center p-4">
          <div class="relative bg-white rounded-xl shadow-2xl max-w-lg w-full" @click.stop>
            <!-- Header -->
            <div class="px-6 py-5 border-b border-gray-200 flex items-center justify-between">
              <h2 class="text-xl font-semibold text-gray-900">手動新增交易</h2>
              <button type="button" @click="closeCreateModal" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
              </button>
            </div>

            <!-- Body -->
            <div class="px-6 py-6 space-y-5">
              <!-- User search -->
              <div class="relative">
                <label class="block text-sm font-semibold text-gray-900 mb-1">會員 Email <span class="text-red-500">*</span></label>
                <input
                  type="text"
                  :value="createForm.user_email"
                  @input="searchUser($event.target.value)"
                  placeholder="輸入 Email 搜尋..."
                  class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                  :class="{ 'border-red-300': createErrors.user_id }"
                />
                <!-- Search results dropdown -->
                <ul v-if="userSearchResults.length > 0" class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-md shadow-lg max-h-48 overflow-auto">
                  <li
                    v-for="user in userSearchResults"
                    :key="user.id"
                    @click="selectUser(user)"
                    class="px-4 py-2 text-sm cursor-pointer hover:bg-indigo-50"
                  >
                    <span class="font-medium">{{ user.email }}</span>
                    <span v-if="user.real_name" class="text-gray-500 ml-2">{{ user.real_name }}</span>
                  </li>
                </ul>
                <p v-if="createErrors.user_id" class="mt-1 text-sm text-red-600">{{ createErrors.user_id }}</p>
                <p v-if="createForm.user_id" class="mt-1 text-xs text-green-600">已選擇會員 ID: {{ createForm.user_id }}</p>
              </div>

              <!-- Course selection -->
              <div>
                <label class="block text-sm font-semibold text-gray-900 mb-1">課程 <span class="text-red-500">*</span></label>
                <select
                  v-model="createForm.course_id"
                  class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                  :class="{ 'border-red-300': createErrors.course_id }"
                >
                  <option value="">請選擇課程</option>
                  <option v-for="course in courses" :key="course.id" :value="course.id">{{ course.name }}</option>
                </select>
                <p v-if="createErrors.course_id" class="mt-1 text-sm text-red-600">{{ createErrors.course_id }}</p>
              </div>

              <!-- Type selection -->
              <div>
                <label class="block text-sm font-semibold text-gray-900 mb-2">交易類型 <span class="text-red-500">*</span></label>
                <div class="flex gap-4">
                  <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" v-model="createForm.type" value="gift" class="text-indigo-600 border-gray-300 focus:ring-indigo-500" />
                    <span class="text-sm">贈送</span>
                  </label>
                  <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" v-model="createForm.type" value="system_assigned" class="text-indigo-600 border-gray-300 focus:ring-indigo-500" />
                    <span class="text-sm">系統指派</span>
                  </label>
                </div>
                <p v-if="createErrors.type" class="mt-1 text-sm text-red-600">{{ createErrors.type }}</p>
              </div>

              <!-- General error -->
              <div v-if="createErrors.general" class="bg-red-50 border border-red-200 rounded-lg p-3">
                <p class="text-sm text-red-600">{{ createErrors.general }}</p>
              </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
              <button
                type="button"
                @click="closeCreateModal"
                :disabled="createSubmitting"
                class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 transition-colors"
              >
                取消
              </button>
              <button
                type="button"
                @click="submitCreate"
                :disabled="createSubmitting"
                class="px-6 py-2.5 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-lg shadow-sm hover:bg-indigo-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {{ createSubmitting ? '新增中...' : '確認新增' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
