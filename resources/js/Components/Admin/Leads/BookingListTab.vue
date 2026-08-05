<script setup>
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import { ref, computed, watch } from 'vue'
import { marked } from 'marked'

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
  dripByEmail: {
    type: Object,
    default: () => ({}),
  },
  purchasesByEmail: {
    type: Object,
    default: () => ({}),
  },
  grantableCourses: {
    type: Array,
    default: () => [],
  },
})

// Status config — single source of truth for every status affordance on this
// page: the square switcher in each row AND the filter pills above the table,
// so one colour always means one status.
// Letters: P=Pending 待面談 / C=Consulted 已面談 / N=No-show 未出席
//          / D=Deal 已成交 / X=Closed 已關閉 / V=Cancelled 已取消
//
// The funnel talks about consultations, not contact attempts (2026-08-05):
// every lead here booked a 1v1 session, so "已聯繫" was describing the wrong
// event. The stored values still read pending / contacted / no_response —
// display-only rename, see the note on each below.
// (class strings are written out in full so Tailwind's scanner keeps them)
const statusButtons = [
  {
    value: 'pending',
    letter: 'P',
    label: '待面談',
    active: 'bg-yellow-500 text-white ring-yellow-500',
    idle: 'bg-yellow-50 text-yellow-700 hover:bg-yellow-200',
    tabActive: 'bg-yellow-500 text-white border-yellow-500 hover:bg-yellow-600',
    tabIdle: 'bg-yellow-50 text-yellow-800 border-yellow-200 hover:bg-yellow-100',
  },
  {
    // Stored as `contacted` — the consultation happened.
    value: 'contacted',
    letter: 'C',
    label: '已面談',
    active: 'bg-blue-500 text-white ring-blue-500',
    idle: 'bg-blue-50 text-blue-700 hover:bg-blue-200',
    tabActive: 'bg-blue-500 text-white border-blue-500 hover:bg-blue-600',
    tabIdle: 'bg-blue-50 text-blue-800 border-blue-200 hover:bg-blue-100',
  },
  {
    // Stored as `no_response` — now means the applicant never showed up.
    // Matches the wording already in the confirmation mail:「無故不出席」.
    value: 'no_response',
    letter: 'N',
    label: '未出席',
    active: 'bg-orange-500 text-white ring-orange-500',
    idle: 'bg-orange-50 text-orange-700 hover:bg-orange-200',
    tabActive: 'bg-orange-500 text-white border-orange-500 hover:bg-orange-600',
    tabIdle: 'bg-orange-50 text-orange-800 border-orange-200 hover:bg-orange-100',
  },
  {
    value: 'converted',
    letter: 'D',
    label: '已成交',
    active: 'bg-green-600 text-white ring-green-600',
    idle: 'bg-green-50 text-green-700 hover:bg-green-200',
    tabActive: 'bg-green-600 text-white border-green-600 hover:bg-green-700',
    tabIdle: 'bg-green-50 text-green-800 border-green-200 hover:bg-green-100',
  },
  {
    value: 'closed',
    letter: 'X',
    label: '已關閉',
    active: 'bg-gray-500 text-white ring-gray-500',
    idle: 'bg-gray-100 text-gray-500 hover:bg-gray-300',
    tabActive: 'bg-gray-500 text-white border-gray-500 hover:bg-gray-600',
    tabIdle: 'bg-gray-100 text-gray-600 border-gray-300 hover:bg-gray-200',
  },
]

// Settable from the row only when there is nothing to undo (FR-054). With a
// live booking the grid's 取消預約 is the only correct path — it also frees the
// slot, kills the Zoom meeting and mails the applicant, none of which a status
// flip does. Without one (the leads carried over from 未回應 have no slot at
// all) there is nothing to release and refusing would be a dead end.
const cancelledStatus = {
  value: 'cancelled',
  letter: 'V',
  label: '已取消',
  active: 'bg-rose-600 text-white ring-rose-600',
  idle: 'bg-rose-50 text-rose-700 hover:bg-rose-200',
  tabActive: 'bg-rose-600 text-white border-rose-600 hover:bg-rose-700',
  tabIdle: 'bg-rose-50 text-rose-800 border-rose-200 hover:bg-rose-100',
}

const allStatuses = [...statusButtons, cancelledStatus]

const hasLiveBooking = (lead) => Boolean(lead.confirmed_at) && !lead.cancelled_at

// Filter tabs — status pills inherit the colour coding above; only 全部 is neutral
const tabs = [
  {
    label: '全部',
    value: '',
    tabActive: 'bg-brand-teal text-white border-brand-teal hover:bg-brand-teal/90',
    tabIdle: 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50',
  },
  ...allStatuses.map(({ value, label, tabActive, tabIdle }) => ({ value, label, tabActive, tabIdle })),
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

// Application questionnaire detail rows (011 US9). Leads created by the old
// one-step form have none of these fields, so the row says so instead of
// rendering a grid of dashes.
const openDetailIds = ref([])

const toggleDetail = (id) => {
  const idx = openDetailIds.value.indexOf(id)
  if (idx === -1) {
    openDetailIds.value.push(id)
  } else {
    openDetailIds.value.splice(idx, 1)
  }
}

const hasApplication = (lead) =>
  Boolean(lead.phone || lead.occupation || lead.bottleneck || lead.expertise || lead.social_url)

const applicationRows = (lead) => {
  const rows = [
    { label: '手機電話', value: lead.phone || '—' },
    { label: '職業和從事時長', value: lead.occupation || '—' },
    { label: '事業瓶頸', value: lead.bottleneck || '—' },
    { label: '知識或能力專長', value: lead.expertise || '—' },
  ]

  if (lead.social_url) {
    rows.push({ label: '經營社群網址', value: lead.social_url, href: lead.social_url })
  }
  if (lead.booking_code) {
    rows.push({ label: '預約優惠碼', value: lead.booking_code })
  }
  if (lead.confirmed_at) {
    rows.push({ label: 'Email 確認時間', value: formatDateTime(lead.confirmed_at) })
  } else {
    rows.push({ label: 'Email 確認', value: '尚未確認' })
  }
  if (lead.zoom_join_url) {
    rows.push({ label: 'Zoom 會議', value: lead.zoom_join_url, href: lead.zoom_join_url })
  }

  return rows
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

// Any status may be notified about a new slot — the admin decides who is
// worth telling, not a status whitelist.
const canNotifySlot = computed(() => selectedIds.value.length > 0)

// Same reasoning as canNotifySlot: the admin ticking the box is the decision.
// A whitelist here meant 已面談 and 已取消 leads — often the warmest ones — sat
// on the list with the button greyed out and no explanation why.
const canSubscribeDrip = computed(() => selectedIds.value.length > 0)

// The one case worth a second look: somebody who already bought does not need
// the sequence that exists to sell to them. Surfaced in the modal rather than
// blocked, because re-nurturing a customer for a *different* course is a real
// thing an admin might mean.
const selectedConverted = computed(() =>
  selectedLeads.value.filter(l => l.status === 'converted')
)

// Inline status update
const updatingStatus = ref(null)

const updateStatus = async (lead, newStatus) => {
  if (lead.status === newStatus) return
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

// Convert lead modal
const showConvertModal = ref(false)
const convertingLead = ref(null)
const convertCourseId = ref('')
const convertAmount = ref(0)
const convertLoading = ref(false)
const convertForce = ref(false)
const convertError = ref('')

const purchaseTypeLabels = {
  paid: '線上付款',
  gift: '贈送',
  system_assigned: '系統開通',
  lead_conversion: '顧問轉換',
}

const convertAmountValid = computed(() => {
  const amount = Number(convertAmount.value)
  return convertAmount.value !== '' && Number.isInteger(amount) && amount >= 0
})

// The purchase this conversion would overwrite, if it came from somewhere else.
// Mirrors the server-side whitelist (FR-015) — a hint only; the guard that
// counts lives in HighTicketLeadService.
const conflictingPurchase = computed(() => {
  const email = convertingLead.value?.email
  const courseId = Number(convertCourseId.value)
  if (!email || !courseId) return null

  const match = (props.purchasesByEmail[email] || [])
    .find(p => p.course_id === courseId)

  if (!match || match.type === 'lead_conversion' || match.status === 'refunded') return null
  return match
})

const openConvertModal = (lead) => {
  convertingLead.value = lead
  convertCourseId.value = props.grantableCourses[0]?.id ?? ''
  convertAmount.value = props.grantableCourses[0]?.display_price ?? 0
  convertForce.value = false
  convertError.value = ''
  showConvertModal.value = true
}

// A warning acknowledged for one course says nothing about the next.
watch(convertCourseId, () => {
  convertForce.value = false
  convertError.value = ''
})

// Deal price defaults to the selected course's current display price;
// the admin overrides it with the actual (offline) deal amount.
watch(convertCourseId, (id) => {
  const course = props.grantableCourses.find(c => c.id === Number(id))
  if (course) convertAmount.value = course.display_price
})

const confirmConvert = async () => {
  if (!convertCourseId.value || !convertingLead.value || !convertAmountValid.value) return
  if (conflictingPurchase.value && !convertForce.value) return
  convertLoading.value = true
  convertError.value = ''
  try {
    const res = await axios.post(`/admin/high-ticket-leads/${convertingLead.value.id}/convert`, {
      course_id: Number(convertCourseId.value),
      amount: Number(convertAmount.value),
      force: convertForce.value,
    })
    const idx = props.leads.data.findIndex(l => l.id === convertingLead.value.id)
    if (idx !== -1) {
      props.leads.data[idx].status = 'converted'
    }
    const base = res.data.user_created
      ? `已完成：為 ${convertingLead.value.name} 建立會員並開通商品`
      : `已完成：為 ${convertingLead.value.name} 開通商品`
    // Never claim a mail went out that did not (D11).
    actionResult.value = res.data.mail_sent
      ? `${base}，開通通知信已寄出`
      : `${base}；開通通知信寄送失敗，請自行聯絡對方`
    showConvertModal.value = false
  } catch (e) {
    // 409 belongs next to the checkbox that resolves it, not in the page banner.
    if (e.response?.status === 409) {
      convertError.value = e.response.data.error
    } else {
      actionResult.value = `開通失敗：${e.response?.data?.message || e.message}`
    }
  } finally {
    convertLoading.value = false
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

// Quick-copy for lead emails; briefly flags which lead id was copied.
const copiedEmailId = ref(null)
const copyEmail = async (lead) => {
  try {
    await navigator.clipboard.writeText(lead.email)
    copiedEmailId.value = lead.id
    setTimeout(() => { if (copiedEmailId.value === lead.id) copiedEmailId.value = null }, 1500)
  } catch (e) {
    console.error('Copy failed', e)
  }
}

// Batch copy of selected emails as a mail-client friendly ", " joined list.
// Deduped because the same email may book the same course more than once.
const selectedEmails = computed(() =>
  [...new Set(selectedLeads.value.map(l => l.email))]
)

const copiedAll = ref(false)
let copiedAllTimeout = null

const copySelectedEmails = async () => {
  if (selectedEmails.value.length === 0) return
  try {
    await navigator.clipboard.writeText(selectedEmails.value.join(', '))
    copiedAll.value = true
    clearTimeout(copiedAllTimeout)
    copiedAllTimeout = setTimeout(() => { copiedAll.value = false }, 2000)
  } catch (e) {
    console.error('Copy failed', e)
    actionResult.value = '複製失敗，請手動選取 Email'
  }
}
</script>

<template>
  <div>
    <!-- Search & course filter -->
    <div class="mb-4 flex flex-col sm:flex-row gap-3">
      <div class="flex-1">
        <input
          v-model="search"
          type="text"
          placeholder="搜尋姓名、Email..."
          class="block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-teal focus:ring-brand-teal sm:text-sm"
        />
      </div>
      <div class="flex items-center gap-2">
        <select
          v-model="courseFilter"
          @change="applyFilters()"
          class="block rounded-md border-gray-300 shadow-sm focus:border-brand-teal focus:ring-brand-teal sm:text-sm"
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
        class="px-4 py-1.5 rounded-full text-sm font-medium border cursor-pointer transition-colors"
        :class="filters.status === (tab.value || null) || (!filters.status && !tab.value)
          ? tab.tabActive
          : tab.tabIdle"
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
        :disabled="selectedIds.length === 0"
        @click="copySelectedEmails"
        class="px-3 py-1.5 text-sm rounded-md border font-medium inline-flex items-center gap-1.5"
        :class="selectedIds.length > 0
          ? (copiedAll
            ? 'bg-green-50 text-green-700 border-green-300 cursor-pointer'
            : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50 cursor-pointer')
          : 'bg-gray-100 text-gray-400 border-gray-200 cursor-not-allowed'"
        title="以逗號分隔複製所選 Email，可直接貼到郵件收件人欄"
      >
        <svg v-if="copiedAll" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
        </svg>
        <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
        </svg>
        {{ copiedAll ? `已複製 ${selectedEmails.length} 個 Email` : '複製 Email' }}
      </button>
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
          ? 'bg-brand-teal text-white border-brand-teal hover:bg-brand-teal/90 cursor-pointer'
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
    <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-lg overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-100">
        <thead class="bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
          <tr>
            <th class="py-3.5 pl-4 pr-3">
              <input
                type="checkbox"
                :checked="allSelected"
                @change="toggleAll"
                class="rounded border-gray-300 text-brand-teal"
              />
            </th>
            <th class="px-4 py-3 text-left">姓名</th>
            <th class="px-4 py-3 text-left">Email</th>
            <th class="hidden md:table-cell w-[173px] px-4 py-3 text-left">課程</th>
            <th class="whitespace-nowrap px-4 py-3 text-left">
              狀態
              <span class="ml-1 font-normal normal-case tracking-normal text-[10px] text-gray-400">P待談 / C已談 / N未出席 / D成 / X關 / V取消</span>
            </th>
            <th class="hidden sm:table-cell whitespace-nowrap w-20 py-3.5 px-2 text-right text-sm font-semibold text-gray-900">通知次數</th>
            <th class="hidden xl:table-cell min-w-56 px-4 py-3 text-left">序列信紀錄</th>
            <th class="hidden lg:table-cell whitespace-nowrap px-4 py-3 text-left">負責顧問</th>
            <th class="hidden lg:table-cell px-4 py-3 text-left">預約時間</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 bg-white">
          <template v-for="lead in leads.data" :key="lead.id">
          <tr class="hover:bg-gray-50">
            <td class="py-4 pl-4 pr-3">
              <input
                type="checkbox"
                :checked="selectedIds.includes(lead.id)"
                @change="toggleSelect(lead.id)"
                class="rounded border-gray-300 text-brand-teal"
              />
            </td>
            <td class="whitespace-nowrap py-4 px-3 text-sm text-gray-900">
              <button
                type="button"
                class="inline-flex items-center gap-1 cursor-pointer hover:text-brand-teal transition"
                :title="hasApplication(lead) ? '展開申請內容' : '這筆沒有申請問卷（舊資料）'"
                @click="toggleDetail(lead.id)"
              >
                <svg
                  class="h-3.5 w-3.5 text-gray-400 transition-transform"
                  :class="{ 'rotate-90': openDetailIds.includes(lead.id) }"
                  fill="none" viewBox="0 0 24 24" stroke="currentColor"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                {{ lead.name }}
              </button>
            </td>
            <td class="whitespace-nowrap py-4 px-3 text-sm text-gray-600">
              <div class="flex items-center gap-2">
                <span>{{ lead.email }}</span>
                <button
                  type="button"
                  class="text-gray-400 hover:text-brand-teal cursor-pointer"
                  :title="copiedEmailId === lead.id ? '已複製' : '複製 Email'"
                  @click="copyEmail(lead)"
                >
                  <svg v-if="copiedEmailId === lead.id" class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                  </svg>
                  <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                  </svg>
                </button>
              </div>
            </td>
            <td class="hidden md:table-cell w-[173px] py-4 px-3 text-sm text-gray-600">
              <div class="flex items-center gap-2">
                <span class="truncate">{{ lead.course?.name ?? '-' }}</span>
                <button
                  v-if="lead.status !== 'converted'"
                  @click="openConvertModal(lead)"
                  class="flex-shrink-0 px-2 py-1 text-xs font-semibold text-white bg-brand-teal rounded shadow-sm hover:bg-brand-teal/90 active:bg-brand-teal transition-colors cursor-pointer"
                >
                  開通
                </button>
              </div>
            </td>
            <td class="whitespace-nowrap py-4 px-3 text-sm">
              <div class="flex items-center gap-1">
                <button
                  v-for="s in allStatuses"
                  :key="s.value"
                  type="button"
                  :disabled="updatingStatus === lead.id || (s.value === 'cancelled' && hasLiveBooking(lead))"
                  :title="s.value === 'cancelled' && hasLiveBooking(lead)
                    ? '這筆有生效中的預約，請到「諮詢時段」取消，那裡才會釋出時段並通知對方'
                    : s.label"
                  :aria-pressed="lead.status === s.value"
                  @click="updateStatus(lead, s.value)"
                  class="h-[25px] w-[25px] flex-shrink-0 rounded text-xs font-bold transition-colors cursor-pointer disabled:opacity-40"
                  :class="[
                    lead.status === s.value ? `${s.active} ring-2 ring-offset-1` : s.idle,
                    // wait = 正在送出；not-allowed = 這顆對這筆本來就不可設定
                    updatingStatus === lead.id ? 'disabled:cursor-wait' : 'disabled:cursor-not-allowed',
                  ]"
                >
                  {{ s.letter }}
                </button>
              </div>
            </td>
            <td class="hidden sm:table-cell w-20 whitespace-nowrap py-4 px-2 text-sm text-right text-gray-600">
              {{ lead.notified_count ?? 0 }}
            </td>
            <td class="hidden xl:table-cell min-w-56 py-4 px-3 text-sm">
              <template v-if="dripByEmail[lead.email]?.length">
                <div class="flex flex-wrap gap-1">
                  <span
                    v-for="sub in dripByEmail[lead.email]"
                    :key="sub.course_name + sub.status"
                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium whitespace-nowrap"
                    :class="{
                      'bg-blue-100 text-blue-800':   sub.status === 'active',
                      'bg-green-100 text-green-800':  sub.status === 'completed',
                      'bg-yellow-100 text-yellow-800': sub.status === 'converted',
                      'bg-gray-100 text-gray-500':    sub.status === 'unsubscribed',
                    }"
                  >
                    {{ sub.course_name }}
                    <span class="opacity-70">·{{ { active: '訂閱中', completed: '已完成', converted: '已轉換', unsubscribed: '已退訂' }[sub.status] ?? sub.status }}</span>
                  </span>
                </div>
              </template>
              <span v-else class="text-gray-400">—</span>
            </td>
            <td class="hidden lg:table-cell whitespace-nowrap py-4 px-3 text-sm text-gray-600">
              <span v-if="lead.consultant">{{ lead.consultant.nickname || lead.consultant.email }}</span>
              <span v-else class="text-gray-400">—</span>
            </td>
            <td class="hidden lg:table-cell whitespace-nowrap py-4 px-3 text-sm text-gray-500">
              {{ formatDateTime(lead.booked_at) }}
            </td>
          </tr>

          <!-- Application questionnaire (011 US9) — rows from the old one-step
               form simply have nothing to show. -->
          <tr v-if="openDetailIds.includes(lead.id)" class="bg-gray-50">
            <td colspan="9" class="px-6 py-4">
              <div v-if="hasApplication(lead)" class="grid gap-3 sm:grid-cols-2 text-sm">
                <div v-for="row in applicationRows(lead)" :key="row.label">
                  <p class="text-xs font-medium text-gray-500">{{ row.label }}</p>
                  <p v-if="row.href" class="mt-0.5">
                    <a :href="row.href" target="_blank" rel="noopener" class="text-brand-teal underline cursor-pointer hover:opacity-70 break-all">
                      {{ row.value }}
                    </a>
                  </p>
                  <p v-else class="mt-0.5 text-gray-800 whitespace-pre-wrap break-words">{{ row.value }}</p>
                </div>
              </div>
              <p v-else class="text-sm text-gray-400">—　這筆預約在申請問卷上線前送出，沒有問卷內容。</p>
            </td>
          </tr>
          </template>

          <tr v-if="leads.data?.length === 0">
            <td colspan="9" class="px-6 py-12 text-center text-gray-500">
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
            class="inline-flex items-center gap-1 text-xs text-brand-teal hover:underline"
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
        <div class="bg-brand-teal/10 border border-brand-teal rounded-lg p-4 flex items-center gap-3">
          <svg class="h-5 w-5 text-brand-teal flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
          <p class="text-sm text-brand-teal">
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
            class="mt-2 block w-full rounded-lg border-gray-300 px-4 py-3 text-base shadow-sm focus:border-brand-teal focus:ring-brand-teal"
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
            class="mt-2 block w-full rounded-lg border-gray-300 px-4 py-3 text-base shadow-sm focus:border-brand-teal focus:ring-brand-teal leading-relaxed"
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
          class="px-6 py-2.5 text-sm font-medium text-white bg-brand-teal rounded-lg hover:bg-brand-teal/90 disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center gap-2"
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
      <!-- Not a block, a heads-up: nurturing an existing customer toward a
           different course is legitimate, doing it by accident is not. -->
      <div
        v-if="selectedConverted.length > 0"
        class="mb-4 rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-sm text-amber-900"
      >
        其中 <strong>{{ selectedConverted.length }} 位已成交</strong>，他們會收到這門課的招生序列信。
        若不是刻意要向他們推另一門課，建議先取消勾選。
      </div>
      <div class="mb-6">
        <label class="block text-sm font-medium text-gray-700 mb-1">序列課程</label>
        <select
          v-model="selectedDripCourseId"
          class="block w-full rounded-md border-gray-300 shadow-sm focus:ring-brand-teal focus:border-brand-teal"
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
          class="px-4 py-2 text-sm font-medium text-white bg-brand-teal border border-transparent rounded-md hover:bg-brand-teal/90 disabled:opacity-50"
        >
          確認加入
        </button>
      </div>
    </div>
    </div>
  </div>
  </Teleport>

  <!-- Convert lead modal -->
  <Teleport to="body">
  <div
    v-if="showConvertModal"
    class="fixed inset-0 z-50 overflow-y-auto"
  >
    <div class="fixed inset-0 bg-black/50" aria-hidden="true" @click="showConvertModal = false" />
    <div class="flex min-h-full items-center justify-center p-4">
    <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-md p-6" @click.stop>
      <h3 class="text-lg font-semibold text-gray-900 mb-1">開通商品</h3>
      <p class="text-sm text-gray-500 mb-5">
        確認後，系統將為此 Lead 完成以下操作：
      </p>

      <!-- Lead info -->
      <div class="mb-5 rounded-lg bg-gray-50 border border-gray-200 px-4 py-3 space-y-1 text-sm">
        <div class="flex gap-2">
          <span class="text-gray-500 w-12 flex-shrink-0">姓名</span>
          <span class="font-medium text-gray-900">{{ convertingLead?.name }}</span>
        </div>
        <div class="flex gap-2">
          <span class="text-gray-500 w-12 flex-shrink-0">Email</span>
          <span class="text-gray-700">{{ convertingLead?.email }}</span>
        </div>
      </div>

      <!-- Actions summary -->
      <ul class="mb-5 space-y-2 text-sm text-gray-700">
        <li class="flex items-start gap-2">
          <svg class="h-4 w-4 text-brand-teal mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
          </svg>
          <span>若尚未註冊，系統將自動建立會員帳號</span>
        </li>
        <li class="flex items-start gap-2">
          <svg class="h-4 w-4 text-brand-teal mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span>開通所選商品（以成交價格入帳）</span>
        </li>
        <li class="flex items-start gap-2">
          <svg class="h-4 w-4 text-brand-teal mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
          </svg>
          <span>Lead 狀態更新為「已成交」</span>
        </li>
        <li class="flex items-start gap-2">
          <svg class="h-4 w-4 text-brand-teal mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
          </svg>
          <span>寄出「顧問成交開通通知」給對方（引導以 Email 驗證碼登入）</span>
        </li>
      </ul>

      <!-- Course selector -->
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">選擇要開通的商品</label>
        <select
          v-model="convertCourseId"
          class="block w-full rounded-md border-gray-300 shadow-sm focus:ring-brand-teal focus:border-brand-teal text-sm"
        >
          <option v-for="course in grantableCourses" :key="course.id" :value="course.id">
            {{ course.name }}
          </option>
        </select>
      </div>

      <!-- Deal amount -->
      <div class="mb-6">
        <label class="block text-sm font-medium text-gray-700 mb-1">成交價格（TWD）</label>
        <input
          v-model.number="convertAmount"
          type="number"
          min="0"
          step="1"
          class="block w-full rounded-md border-gray-300 shadow-sm focus:ring-brand-teal focus:border-brand-teal text-sm"
        />
        <p class="mt-1 text-xs text-gray-500">預設帶入課程目前售價，可改為實際成交金額（私下匯款請填實收金額）；填 0 表示免費開通、不計營收</p>
        <p v-if="!convertAmountValid" class="mt-1 text-xs text-red-600">請輸入 0 或正整數金額</p>
      </div>

      <!-- Overwrite guard: this course was already bought through another channel -->
      <div v-if="conflictingPurchase" class="mb-6 rounded-md border border-amber-300 bg-amber-50 p-3">
        <div class="flex items-start gap-2">
          <svg class="h-4 w-4 text-amber-600 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
          </svg>
          <div class="text-sm text-amber-900">
            <p class="font-medium">此會員已有本課程的購買紀錄</p>
            <p class="mt-1 text-xs">
              類型「{{ purchaseTypeLabels[conflictingPurchase.type] || conflictingPurchase.type }}」·
              金額 NT$ {{ conflictingPurchase.amount.toLocaleString() }}
            </p>
            <p class="mt-1 text-xs">開通會把該筆紀錄覆寫為「顧問轉換」並改為上方成交價格，原始金額無法還原。</p>
            <label class="mt-2 flex items-center gap-2 cursor-pointer hover:text-amber-700">
              <input v-model="convertForce" type="checkbox" class="rounded border-amber-400 text-amber-600 focus:ring-amber-500 cursor-pointer" />
              <span class="text-xs font-medium">我了解，仍要覆寫這筆紀錄</span>
            </label>
          </div>
        </div>
      </div>

      <p v-if="convertError" class="mb-4 rounded-md bg-red-50 px-3 py-2 text-xs text-red-700">{{ convertError }}</p>

      <div class="flex justify-end gap-3">
        <button
          @click="showConvertModal = false"
          :disabled="convertLoading"
          class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50"
        >
          取消
        </button>
        <button
          @click="confirmConvert"
          :disabled="!convertCourseId || !convertAmountValid || convertLoading || (conflictingPurchase && !convertForce)"
          class="px-4 py-2 text-sm font-medium text-white bg-brand-teal rounded-lg hover:bg-brand-teal/90 disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center gap-2"
        >
          <svg v-if="convertLoading" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
          </svg>
          {{ convertLoading ? '處理中...' : '確認開通' }}
        </button>
      </div>
    </div>
    </div>
  </div>
  </Teleport>
</template>
