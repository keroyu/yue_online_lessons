<script setup>
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import axios from 'axios'
import { ref, computed, watch } from 'vue'
import { marked } from 'marked'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  leads: {
    type: Object,
    required: true,
  },
  filters: {
    type: Object,
    required: true,
  },
  highTicketCourses: {
    type: Array,
    default: () => [],
  },
  dripCourses: {
    type: Array,
    required: true,
  },
  notifyTemplate: {
    type: Object,
    default: null,
  },
})

// Status config
const statusLabels = {
  pending: '待聯繫',
  contacted: '已聯繫',
  converted: '已成交',
  closed: '已關閉',
}

const statusClasses = {
  pending: 'bg-yellow-100 text-yellow-800',
  contacted: 'bg-blue-100 text-blue-800',
  converted: 'bg-green-100 text-green-800',
  closed: 'bg-gray-100 text-gray-800',
}

const statusOptions = ['pending', 'contacted', 'converted', 'closed']

// Filter tabs
const tabs = [
  { label: '全部', value: '' },
  { label: '待聯繫', value: 'pending' },
  { label: '已聯繫', value: 'contacted' },
  { label: '已成交', value: 'converted' },
  { label: '已關閉', value: 'closed' },
]

// Search & course filter
const search = ref(props.filters.search || '')
const courseFilter = ref(props.filters.course_id || '')

let searchTimeout = null
watch(search, () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(applyFilters, 300)
})

const applyFilters = (overrides = {}) => {
  router.get('/admin/high-ticket-leads', {
    status: props.filters.status || undefined,
    search: search.value || undefined,
    course_id: courseFilter.value || undefined,
    ...overrides,
  }, { preserveState: true, replace: true })
}

const applyFilter = (status) => {
  router.get('/admin/high-ticket-leads', {
    status: status || undefined,
    search: search.value || undefined,
    course_id: courseFilter.value || undefined,
  }, {
    preserveState: true,
    replace: true,
  })
}

// Selection
const selectedIds = ref([])

const toggleSelect = (id) => {
  const idx = selectedIds.value.indexOf(id)
  if (idx === -1) {
    selectedIds.value.push(id)
  } else {
    selectedIds.value.splice(idx, 1)
  }
}

const toggleAll = () => {
  const allIds = props.leads.data.map(l => l.id)
  if (selectedIds.value.length === allIds.length) {
    selectedIds.value = []
  } else {
    selectedIds.value = [...allIds]
  }
}

const allSelected = computed(() =>
  props.leads.data.length > 0 &&
  selectedIds.value.length === props.leads.data.length
)

// Determine which selected leads are pending
const selectedLeads = computed(() =>
  props.leads.data.filter(l => selectedIds.value.includes(l.id))
)

const canNotifySlot = computed(() =>
  selectedIds.value.length > 0 &&
  selectedLeads.value.every(l => l.status === 'pending')
)

const canSubscribeDrip = computed(() =>
  selectedIds.value.length > 0 &&
  selectedLeads.value.every(l => ['pending', 'closed'].includes(l.status))
)

// Inline status update
const updatingStatus = ref(null)

const updateStatus = async (lead, newStatus) => {
  updatingStatus.value = lead.id
  try {
    const res = await axios.patch(`/admin/high-ticket-leads/${lead.id}/status`, { status: newStatus })
    // Update local data
    const idx = props.leads.data.findIndex(l => l.id === lead.id)
    if (idx !== -1) {
      props.leads.data[idx].status = res.data.status
    }
  } catch (e) {
    console.error('Status update failed', e)
  } finally {
    updatingStatus.value = null
  }
}

// Notify slot batch action
const actionResult = ref(null)
const actionLoading = ref(false)
const showNotifyModal = ref(false)

const openNotifyModal = () => {
  showNotifyModal.value = true
}

const notifySlot = async () => {
  showNotifyModal.value = false
  actionLoading.value = true
  actionResult.value = null
  try {
    const res = await axios.post('/admin/high-ticket-leads/notify-slot', {
      lead_ids: selectedIds.value,
    })
    actionResult.value = `已排送通知 ${res.data.dispatched} 封`
    selectedIds.value = []
  } catch (e) {
    actionResult.value = `失敗：${e.response?.data?.error || e.message}`
  } finally {
    actionLoading.value = false
  }
}

// Subscribe drip modal
const showDripModal = ref(false)
const selectedDripCourseId = ref('')

const openDripModal = () => {
  selectedDripCourseId.value = props.dripCourses[0]?.id ?? ''
  showDripModal.value = true
}

const subscribeDrip = async () => {
  if (!selectedDripCourseId.value) return
  actionLoading.value = true
  actionResult.value = null
  showDripModal.value = false
  try {
    const res = await axios.post('/admin/high-ticket-leads/subscribe-drip', {
      lead_ids: selectedIds.value,
      drip_course_id: Number(selectedDripCourseId.value),
    })
    actionResult.value = `已派送 ${res.data.dispatched} 人，略過 ${res.data.skipped} 人（已有 active 序列）`
    selectedIds.value = []
  } catch (e) {
    actionResult.value = `失敗：${e.response?.data?.message || e.message}`
  } finally {
    actionLoading.value = false
  }
}

// Batch email modal
const showBatchEmailModal = ref(false)
const batchEmailSubject = ref('')
const batchEmailBody = ref('')
const batchEmailErrors = ref({})
const batchEmailSending = ref(false)

const openBatchEmailModal = () => {
  batchEmailSubject.value = ''
  batchEmailBody.value = ''
  batchEmailErrors.value = {}
  showBatchEmailModal.value = true
}

const sendBatchEmail = async () => {
  batchEmailErrors.value = {}
  if (!batchEmailSubject.value.trim()) {
    batchEmailErrors.value.subject = '主旨為必填'
    return
  }
  if (!batchEmailBody.value.trim()) {
    batchEmailErrors.value.body = '內容為必填'
    return
  }
  batchEmailSending.value = true
  try {
    const res = await axios.post('/admin/high-ticket-leads/batch-email', {
      lead_ids: selectedIds.value,
      subject: batchEmailSubject.value,
      body: batchEmailBody.value,
    })
    actionResult.value = res.data.message || `已發送 ${res.data.sent_count} 封郵件`
    showBatchEmailModal.value = false
    selectedIds.value = []
  } catch (e) {
    batchEmailErrors.value.general = e.response?.data?.message || '發送失敗，請稍後再試'
  } finally {
    batchEmailSending.value = false
  }
}

// Pagination
const goToPage = (page) => {
  router.get('/admin/high-ticket-leads', {
    page,
    status: props.filters.status || undefined,
    search: search.value || undefined,
    course_id: courseFilter.value || undefined,
  }, { preserveState: true })
}

const formatDateTime = (str) => {
  if (!str) return '-'
  return new Date(str).toLocaleString('zh-TW')
}
</script>

<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="mb-6">
      <h1 class="text-2xl font-semibold text-gray-900">Leads 名單</h1>
      <p class="mt-1 text-sm text-gray-600">客製服務預約訪客管理</p>
    </div>

    <!-- Search & course filter -->
    <div class="mb-4 flex flex-col sm:flex-row gap-3">
      <div class="flex-1">
        <input
          v-model="search"
          type="text"
          placeholder="搜尋姓名、Email..."
          class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
        />
      </div>
      <div class="flex items-center gap-2">
        <select
          v-model="courseFilter"
          @change="applyFilters()"
          class="block rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
        >
          <option value="">所有課程</option>
          <option v-for="course in highTicketCourses" :key="course.id" :value="course.id">
            {{ course.name }}
          </option>
        </select>
        <button
          v-if="courseFilter"
          @click="courseFilter = ''; applyFilters()"
          class="text-gray-400 hover:text-gray-600"
          title="清除篩選"
        >
          <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Status filter tabs -->
    <div class="mb-4 flex gap-2 flex-wrap">
      <button
        v-for="tab in tabs"
        :key="tab.value"
        @click="applyFilter(tab.value)"
        class="px-4 py-1.5 rounded-full text-sm font-medium border"
        :class="filters.status === (tab.value || null) || (!filters.status && !tab.value)
          ? 'bg-indigo-600 text-white border-indigo-600'
          : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50'"
      >
        {{ tab.label }}
      </button>
    </div>

    <!-- Action result -->
    <div v-if="actionResult" class="mb-4 rounded-md bg-blue-50 border border-blue-200 px-4 py-2 text-sm text-blue-800">
      {{ actionResult }}
    </div>

    <!-- Batch actions -->
    <div class="mb-4 flex flex-wrap items-center gap-3">
      <span class="text-sm text-gray-500">已選 {{ selectedIds.length }} 筆</span>
      <button
        :disabled="!canNotifySlot || actionLoading"
        @click="openNotifyModal"
        class="px-3 py-1.5 text-sm rounded-md border font-medium"
        :class="canNotifySlot && !actionLoading
          ? 'bg-orange-500 text-white border-orange-500 hover:bg-orange-600 cursor-pointer'
          : 'bg-gray-100 text-gray-400 border-gray-200 cursor-not-allowed'"
      >
        通知新時段
      </button>
      <button
        :disabled="!canSubscribeDrip || actionLoading"
        @click="openDripModal"
        class="px-3 py-1.5 text-sm rounded-md border font-medium"
        :class="canSubscribeDrip && !actionLoading
          ? 'bg-indigo-600 text-white border-indigo-600 hover:bg-indigo-700 cursor-pointer'
          : 'bg-gray-100 text-gray-400 border-gray-200 cursor-not-allowed'"
      >
        加入序列信
      </button>
      <button
        :disabled="selectedIds.length === 0 || actionLoading"
        @click="openBatchEmailModal"
        class="px-3 py-1.5 text-sm rounded-md border font-medium"
        :class="selectedIds.length > 0 && !actionLoading
          ? 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50 cursor-pointer'
          : 'bg-gray-100 text-gray-400 border-gray-200 cursor-not-allowed'"
      >
        發送郵件
      </button>
      <span class="text-xs text-gray-400 leading-snug">
        新時段釋出時，你可以通知客戶來預約；<br class="hidden sm:inline">若無法聯絡客戶／未成交，考慮加入序列信進行自動轉化。
      </span>
    </div>

    <!-- Table -->
    <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
      <table class="min-w-full divide-y divide-gray-300">
        <thead class="bg-gray-50">
          <tr>
            <th class="py-3.5 pl-4 pr-3">
              <input
                type="checkbox"
                :checked="allSelected"
                @change="toggleAll"
                class="rounded border-gray-300 text-indigo-600"
              />
            </th>
            <th class="py-3.5 px-3 text-left text-sm font-semibold text-gray-900">姓名</th>
            <th class="py-3.5 px-3 text-left text-sm font-semibold text-gray-900">Email</th>
            <th class="hidden md:table-cell py-3.5 px-3 text-left text-sm font-semibold text-gray-900">課程</th>
            <th class="py-3.5 px-3 text-left text-sm font-semibold text-gray-900">狀態</th>
            <th class="hidden sm:table-cell py-3.5 px-3 text-right text-sm font-semibold text-gray-900">通知次數</th>
            <th class="hidden lg:table-cell py-3.5 px-3 text-left text-sm font-semibold text-gray-900">預約時間</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 bg-white">
          <tr v-for="lead in leads.data" :key="lead.id" class="hover:bg-gray-50">
            <td class="py-4 pl-4 pr-3">
              <input
                type="checkbox"
                :checked="selectedIds.includes(lead.id)"
                @change="toggleSelect(lead.id)"
                class="rounded border-gray-300 text-indigo-600"
              />
            </td>
            <td class="whitespace-nowrap py-4 px-3 text-sm text-gray-900">{{ lead.name }}</td>
            <td class="whitespace-nowrap py-4 px-3 text-sm text-gray-600">{{ lead.email }}</td>
            <td class="hidden md:table-cell whitespace-nowrap py-4 px-3 text-sm text-gray-600">
              {{ lead.course?.name ?? '-' }}
            </td>
            <td class="whitespace-nowrap py-4 px-3 text-sm">
              <select
                :value="lead.status"
                :disabled="updatingStatus === lead.id"
                @change="updateStatus(lead, $event.target.value)"
                class="rounded border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500"
                :class="statusClasses[lead.status]"
              >
                <option v-for="s in statusOptions" :key="s" :value="s">{{ statusLabels[s] }}</option>
              </select>
            </td>
            <td class="hidden sm:table-cell whitespace-nowrap py-4 px-3 text-sm text-right text-gray-600">
              {{ lead.notified_count ?? 0 }}
            </td>
            <td class="hidden lg:table-cell whitespace-nowrap py-4 px-3 text-sm text-gray-500">
              {{ formatDateTime(lead.booked_at) }}
            </td>
          </tr>
          <tr v-if="leads.data?.length === 0">
            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
              {{ filters.status ? '沒有符合條件的 Leads' : '尚無預約記錄' }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="leads.last_page > 1" class="mt-4 flex items-center justify-between">
      <div class="text-sm text-gray-700">
        顯示第 {{ (leads.current_page - 1) * leads.per_page + 1 }} - {{ Math.min(leads.current_page * leads.per_page, leads.total) }} 筆，共 {{ leads.total }} 筆
      </div>
      <nav class="flex items-center space-x-2">
        <button
          @click="goToPage(leads.current_page - 1)"
          :disabled="leads.current_page === 1"
          class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          上一頁
        </button>
        <span class="text-sm text-gray-700">{{ leads.current_page }} / {{ leads.last_page }}</span>
        <button
          @click="goToPage(leads.current_page + 1)"
          :disabled="leads.current_page === leads.last_page"
          class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          下一頁
        </button>
      </nav>
    </div>
  </div>

  <!-- Notify slot confirmation modal -->
  <div
    v-if="showNotifyModal"
    class="fixed inset-0 z-50 flex items-center justify-center"
  >
    <div class="fixed inset-0 bg-black bg-opacity-40" @click="showNotifyModal = false" />
    <div class="relative bg-white rounded-lg shadow-xl p-6 w-full max-w-lg mx-4 max-h-[90vh] overflow-y-auto">
      <h3 class="text-lg font-semibold text-gray-900 mb-4">確認發送通知</h3>

      <!-- No template warning -->
      <div v-if="!notifyTemplate" class="mb-4 rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
        找不到「客製服務新時段通知」Email 模板，請先至
        <a href="/admin/email-templates" class="underline font-medium">Email 模板管理</a>
        建立後再發送。
      </div>

      <template v-else>
        <!-- Template preview -->
        <div class="mb-4 rounded-md border border-gray-200 bg-gray-50 p-4 space-y-3">
          <div>
            <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">主旨</span>
            <p class="mt-1 text-sm text-gray-800 font-medium">{{ notifyTemplate.subject }}</p>
          </div>
          <div>
            <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">內容預覽</span>
            <div
              class="mt-1 text-sm text-gray-700 prose prose-sm max-w-none"
              v-html="marked(notifyTemplate.body_md)"
            />
          </div>
          <a
            :href="`/admin/email-templates/${notifyTemplate.id}/edit`"
            target="_blank"
            class="inline-flex items-center gap-1 text-xs text-indigo-600 hover:underline"
          >
            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 012.828 2.828L11.828 15.828A2 2 0 019 16H7v-2a2 2 0 01.586-1.414z" />
            </svg>
            在新分頁編輯模板
          </a>
        </div>

        <!-- Recipients -->
        <p class="text-sm text-gray-600 mb-2">收件人（{{ selectedIds.length }} 位）：</p>
        <ul class="mb-5 max-h-32 overflow-y-auto rounded-md border border-gray-200 bg-white divide-y divide-gray-100 text-sm text-gray-700">
          <li
            v-for="lead in selectedLeads"
            :key="lead.id"
            class="px-3 py-1.5 flex justify-between"
          >
            <span>{{ lead.name }}</span>
            <span class="text-gray-400">{{ lead.email }}</span>
          </li>
        </ul>
      </template>

      <div class="flex justify-end gap-3">
        <button
          @click="showNotifyModal = false"
          class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
        >
          取消
        </button>
        <button
          :disabled="!notifyTemplate"
          @click="notifySlot"
          class="px-4 py-2 text-sm font-medium text-white bg-orange-500 border border-transparent rounded-md hover:bg-orange-600 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          確認發送
        </button>
      </div>
    </div>
  </div>

  <!-- Batch email modal -->
  <Teleport to="body">
  <div
    v-if="showBatchEmailModal"
    class="fixed inset-0 z-50 overflow-y-auto"
  >
    <div class="fixed inset-0 bg-black/50" aria-hidden="true" @click="showBatchEmailModal = false" />
    <div class="flex min-h-full items-center justify-center p-4">
    <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-2xl flex flex-col" @click.stop>
      <!-- Header -->
      <div class="px-6 py-5 border-b border-gray-200 flex justify-between items-start">
        <div>
          <h3 class="text-xl font-semibold text-gray-900">發送批次郵件</h3>
          <p class="mt-1 text-sm text-gray-500">編輯郵件內容並發送給選取的 Leads</p>
        </div>
        <button
          type="button"
          class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100"
          @click="showBatchEmailModal = false"
        >
          <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Content -->
      <div class="px-6 py-6 overflow-y-auto space-y-5">
        <!-- Recipient info -->
        <div class="bg-indigo-50 border border-indigo-100 rounded-lg p-4 flex items-center gap-3">
          <svg class="h-5 w-5 text-indigo-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
          <p class="text-sm text-indigo-800">
            將發送郵件給 <strong>{{ selectedIds.length }}</strong> 位 Lead
          </p>
        </div>

        <!-- General error -->
        <div v-if="batchEmailErrors.general" class="bg-red-50 border border-red-200 rounded-lg p-4 text-sm text-red-600">
          {{ batchEmailErrors.general }}
        </div>

        <!-- Subject -->
        <div>
          <label class="block text-sm font-semibold text-gray-900">
            郵件主旨 <span class="text-red-500">*</span>
          </label>
          <input
            v-model="batchEmailSubject"
            type="text"
            placeholder="請輸入郵件主旨"
            :disabled="batchEmailSending"
            class="mt-2 block w-full rounded-lg border-gray-300 px-4 py-3 text-base shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': batchEmailErrors.subject }"
          />
          <div class="mt-1 flex justify-between">
            <span class="text-sm text-red-600">{{ batchEmailErrors.subject || '' }}</span>
            <span class="text-sm" :class="batchEmailSubject.length > 200 ? 'text-red-600' : 'text-gray-400'">
              {{ batchEmailSubject.length }} / 200
            </span>
          </div>
        </div>

        <!-- Body -->
        <div>
          <label class="block text-sm font-semibold text-gray-900">
            郵件內容 <span class="text-red-500">*</span>
          </label>
          <textarea
            v-model="batchEmailBody"
            rows="10"
            placeholder="請輸入郵件內容..."
            :disabled="batchEmailSending"
            class="mt-2 block w-full rounded-lg border-gray-300 px-4 py-3 text-base shadow-sm focus:border-indigo-500 focus:ring-indigo-500 leading-relaxed"
            :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': batchEmailErrors.body }"
          ></textarea>
          <div class="mt-1 flex justify-between">
            <span class="text-sm text-red-600">{{ batchEmailErrors.body || '' }}</span>
            <span class="text-sm" :class="batchEmailBody.length > 10000 ? 'text-red-600' : 'text-gray-400'">
              {{ batchEmailBody.length }} / 10000
            </span>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="px-6 py-5 border-t border-gray-200 flex justify-end gap-3">
        <button
          type="button"
          class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
          @click="showBatchEmailModal = false"
          :disabled="batchEmailSending"
        >
          取消
        </button>
        <button
          type="button"
          class="px-6 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center gap-2"
          @click="sendBatchEmail"
          :disabled="batchEmailSending"
        >
          <template v-if="batchEmailSending">
            <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
            </svg>
            發送中...
          </template>
          <template v-else>
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
            發送郵件
          </template>
        </button>
      </div>
    </div>
    </div>
  </div>
  </Teleport>

  <!-- Drip course selection modal -->
  <Teleport to="body">
  <div
    v-if="showDripModal"
    class="fixed inset-0 z-50 overflow-y-auto"
  >
    <div class="fixed inset-0 bg-black/50" aria-hidden="true" @click="showDripModal = false" />
    <div class="flex min-h-full items-center justify-center p-4">
    <div class="relative bg-white rounded-lg shadow-xl p-6 w-full max-w-md" @click.stop>
      <h3 class="text-lg font-semibold text-gray-900 mb-4">選擇序列課程</h3>
      <p class="text-sm text-gray-600 mb-4">
        將為 {{ selectedIds.length }} 位 Lead 加入序列信（已有 active 訂閱者將略過）。
      </p>
      <div class="mb-6">
        <label class="block text-sm font-medium text-gray-700 mb-1">序列課程</label>
        <select
          v-model="selectedDripCourseId"
          class="block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
        >
          <option v-for="course in dripCourses" :key="course.id" :value="course.id">
            {{ course.name }}
          </option>
        </select>
        <p v-if="dripCourses.length === 0" class="mt-2 text-sm text-red-600">
          目前沒有序列課程可選擇
        </p>
      </div>
      <div class="flex justify-end gap-3">
        <button
          @click="showDripModal = false"
          class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
        >
          取消
        </button>
        <button
          :disabled="!selectedDripCourseId"
          @click="subscribeDrip"
          class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 disabled:opacity-50"
        >
          確認加入
        </button>
      </div>
    </div>
    </div>
  </div>
  </Teleport>
</template>
