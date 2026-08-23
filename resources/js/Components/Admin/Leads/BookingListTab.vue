<script setup>
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import { ref, computed, watch } from 'vue'
import { marked } from 'marked'
import ConsultationNotesPanel from '@/Components/Admin/Leads/ConsultationNotesPanel.vue'
import ConsultationSummaryModal from '@/Components/Admin/Leads/ConsultationSummaryModal.vue'

const props = defineProps({
  leads: {
    type: Object,
    required: true,
  },
  filters: {
    type: Object,
    required: true,
  },
  // Admins + sales consultants, for the owner filter (US18)
  consultantOptions: {
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
  // Raw body of the 預約婉拒通知 template, so the confirm dialog can quote the
  // mail that will actually go out (US27 / FR-141). Null when it is missing,
  // which is what disables the button.
  declineReason: {
    type: String,
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
  suppressionsByEmail: {
    type: Object,
    default: () => ({}),
  },
  grantableCourses: {
    type: Array,
    default: () => [],
  },
  // { status: count } over the whole search/course/consultant-filtered set,
  // status filter excluded — see the funnel share on the pills below.
  conversionStats: {
    type: Object,
    default: () => ({ month: { people: 0, amount: 0 }, year: { people: 0, amount: 0 } }),
  },
  statusCounts: {
    type: Object,
    default: () => ({}),
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

// Set by the screening, not by hand (011 US24 / FR-127) — but still settable
// from the row, because the whole point of D97 is that a refusal is a snapshot
// of somebody's situation, not a verdict on them.
const declinedStatus = {
  value: 'declined',
  letter: 'R',
  label: '已婉拒',
  active: 'bg-rose-400 text-white ring-rose-400',
  idle: 'bg-rose-50 text-rose-600 hover:bg-rose-200',
  tabActive: 'bg-rose-400 text-white border-rose-400 hover:bg-rose-500',
  tabIdle: 'bg-rose-50 text-rose-700 border-rose-200 hover:bg-rose-100',
}

const allStatuses = [...statusButtons, cancelledStatus, declinedStatus]

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

// Funnel share on each status pill. The denominator is every lead matching the
// current search / course filter, so the numbers stay put when the admin clicks
// into a status instead of collapsing to 100%.
const leadTotal = computed(() =>
  Object.values(props.statusCounts).reduce((sum, n) => sum + Number(n || 0), 0)
)

const statusCount = (value) => Number(props.statusCounts[value] || 0)

// A status holding a handful of leads out of hundreds is still worth seeing —
// show it as <1% rather than letting it round away to 0%.
// 全部 carries the absolute count — it is the denominator every percentage
// next to it is read against, so it has no share of its own to show.
const tabMetric = (value) => {
  if (!leadTotal.value) return null
  if (!value) return `${leadTotal.value} 筆`
  const pct = Math.round((statusCount(value) / leadTotal.value) * 100)
  if (pct === 0) return statusCount(value) > 0 ? '<1%' : '0%'
  return `${pct}%`
}

const tabTitle = (value) => {
  if (!leadTotal.value) return ''
  return value
    ? `${statusCount(value)} 筆 / 共 ${leadTotal.value} 筆`
    : `共 ${leadTotal.value} 筆`
}

// Search, meeting-time & consultant filter. All three stack, and every
// navigation away from here has to carry them all — dropping one silently
// widens the list under the admin without the URL saying so.
const search = ref(props.filters.search || '')
const consultantFilter = ref(props.filters.consultant || '')

// Meeting-time quick filter (US28): '' | 'today' | '7d'. The server normalises
// an unrecognised key to null, so nothing lights up for a URL nobody wrote.
const metFilter = ref(props.filters.met || '')

// Labels live next to the keys the server understands (FR-144).
const metPresets = [
  { value: 'today', label: '今日', hint: '面談時段落在今天（台北時間）的預約，含今天稍晚才要進行的場次' },
  { value: '7d', label: '近 7 日', hint: '面談時段落在今天往前算七個日曆日內的預約，不含明天以後' },
  { value: 'tomorrow', label: '明日', hint: '面談時段落在明天（台北時間）的預約 — 這是備課用的名單，不是追銷名單' },
]

// Pressing the active one again clears it — two buttons and no "所有時間"
// option, because the cleared state is the whole list and that is already what
// the pills above are showing.
const toggleMet = (value) => {
  metFilter.value = metFilter.value === value ? '' : value
  applyFilters()
}

const hasAnyFilter = computed(() =>
  Boolean(props.filters.status || search.value || metFilter.value || consultantFilter.value)
)

let searchTimeout = null
watch(search, () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(applyFilters, 300)
})

const applyFilters = (overrides = {}) => {
  router.get('/admin/high-ticket-leads', {
    status: props.filters.status || undefined,
    search: search.value || undefined,
    met: metFilter.value || undefined,
    consultant: consultantFilter.value || undefined,
    ...overrides,
  }, { preserveState: true, replace: true })
}

const applyFilter = (status) => {
  router.get('/admin/high-ticket-leads', {
    status: status || undefined,
    search: search.value || undefined,
    met: metFilter.value || undefined,
    consultant: consultantFilter.value || undefined,
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

// Questionnaire only. Screening has its own block with its own guard, and
// folding it in here made every screened-but-unfinished lead render the row of
// 「—」 that US9 explicitly asked not to exist.
const hasApplication = (lead) =>
  Boolean(lead.phone || lead.occupation || lead.bottleneck || lead.expertise || lead.social_url)

// 資格審核 (011 FR-131). Scores are admin-only — the applicant is never shown
// one, so this is the single place the number appears at all.
const SCREENING_TIERS = {
  hot:  { label: '高購買意願', class: 'bg-green-100 text-green-800' },
  warm: { label: '值得談', class: 'bg-amber-100 text-amber-800' },
  cold: { label: '培育名單', class: 'bg-gray-100 text-gray-600' },
}

const screeningTier = (lead) => SCREENING_TIERS[lead.screening_tier] ?? null

/**
 * The badge shortcut: straight to the newest summary (011 FR-121).
 *
 * Reading the summary is the whole reason to open one of these rows, and making
 * that a two-step — expand, then find the button — is a tax on the common case.
 *
 * Counted over sessions that actually have a summary, not over sessions. A badge
 * is a promise that there is something to read: a booking made this morning has
 * a record and nothing in it, and offering to open that is a wasted click. The
 * empty sessions are still listed inside the expanded row.
 */
const summarised = (lead) =>
  (lead.consultation_notes ?? []).filter((note) => (note.summary ?? '').trim() !== '')

const quickNote = ref(null)

const openSummary = (lead) => {
  // Notes arrive newest-first, so the first one still holding a summary is the
  // most recent thing worth reading.
  quickNote.value = summarised(lead)[0] ?? null
}

const pad2 = (n) => String(n).padStart(2, '0')

// Consultation slots are 15-minute units (ConsultationSlot::UNIT_MINUTES);
// a booking is however many consecutive units the lead holds (`lead.slots`,
// eager-loaded ordered by starts_at), so the display range is just the first
// unit's start to the last unit's start + 15 minutes.
const formatSlotRange = (lead) => {
  if (!lead.slots?.length) return null
  const starts = lead.slots.map(s => new Date(s.starts_at))
  const start = starts[0]
  const end = new Date(starts[starts.length - 1].getTime() + 15 * 60 * 1000)
  const time = (d) => `${pad2(d.getHours())}:${pad2(d.getMinutes())}`
  return `${start.getFullYear()}/${start.getMonth() + 1}/${start.getDate()} ${time(start)}-${time(end)}`
}

// Status wording for the drip badge's tooltip. `booked` is the one that matters
// most here and the one the badge used to render as a raw English word: it means
// the sequence stopped because this person booked, which is what makes the
// number beside the slug read as "converted after N emails" (FR-150).
const dripStatusLabels = {
  active: '訂閱中',
  booked: '已預約停信',
  completed: '已完成',
  converted: '已轉換',
  unsubscribed: '已退訂',
}

// Earliest drip subscription across every course this email has ever joined —
// answers "how long has this person been warmed", not "which course now".
const firstDripSubscribedAt = (lead) => {
  const subs = props.dripByEmail[lead.email]
  if (!subs?.length) return null
  return subs.reduce((earliest, s) => (
    !earliest || new Date(s.subscribed_at) < new Date(earliest) ? s.subscribed_at : earliest
  ), null)
}

// Calendar-day difference (not full 24h), so the number does not flicker
// depending on what time of day the admin happens to look.
//
// Counts to the booking's own confirmation time once it has one — the days
// from "started receiving the sequence" to "confirmed this booking" is a
// fixed fact about that conversion and should read the same forever, not
// keep climbing every day the admin happens to reopen the row. Before
// confirmation there is no such fixed endpoint yet, so it falls back to
// counting up to today.
const formatDripStart = (dateStr, confirmedAtStr) => {
  const d = new Date(dateStr)
  const dateLabel = `${d.getFullYear()}/${d.getMonth() + 1}/${d.getDate()}`
  const timeLabel = `${pad2(d.getHours())}:${pad2(d.getMinutes())}`
  const startOfDay = (x) => new Date(x.getFullYear(), x.getMonth(), x.getDate())
  const reference = confirmedAtStr ? new Date(confirmedAtStr) : new Date()
  const days = Math.round((startOfDay(reference) - startOfDay(d)) / 86400000)
  return `${dateLabel} ${timeLabel}（經過 ${days} 天）`
}

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

  return rows
}

/**
 * Booking facts, kept out of applicationRows on purpose: they are true of every
 * lead, including the pre-questionnaire ones whose detail panel used to say
 * "no questionnaire content" and then show nothing at all — not even when the
 * consultation was scheduled.
 */
const bookingRows = (lead) => {
  const rows = []

  const slotRange = formatSlotRange(lead)
  rows.push({ label: '諮詢時段', value: slotRange || '尚未有時段' })

  // The submission time used to occupy the list column labelled 預約時間, which
  // made rescheduling look broken: moving a consultation never changes when the
  // application was sent.
  rows.push({ label: '申請送出時間', value: formatDateTime(lead.booked_at) })

  if (lead.confirmed_at) {
    rows.push({ label: 'Email 確認時間', value: formatDateTime(lead.confirmed_at) })
  } else {
    rows.push({ label: 'Email 確認', value: '尚未確認' })
  }
  const dripStart = firstDripSubscribedAt(lead)
  if (dripStart) {
    rows.push({ label: '序列信起始時間', value: formatDripStart(dripStart, lead.confirmed_at) })
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

// ---- decline + cancel in one action (011 US27) -----------------------------
const decliningId = ref(null)
const declineTarget = ref(null)

const confirmDecline = () => {
  const lead = declineTarget.value
  if (!lead) return

  declineTarget.value = null
  decliningId.value = lead.id

  // Inertia rather than the axios path the status squares use (D108): this one
  // action moves the status, two timestamps and the slot column at once, and a
  // reload is cheaper than keeping a second copy of those rules in here.
  router.post(`/admin/high-ticket-leads/${lead.id}/decline`, {}, {
    preserveScroll: true,
    onFinish: () => { decliningId.value = null },
  })
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
const convertPlanId = ref('')
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

// Tiers of the selected course (011 US21); empty for every course without them.
const convertCoursePlans = computed(() =>
  props.grantableCourses.find(c => c.id === Number(convertCourseId.value))?.plans || []
)

// A tiered course must be sold as one specific tier. The server enforces this
// too (FR-092) — this only stops the request from being made.
const convertPlanValid = computed(() =>
  convertCoursePlans.value.length === 0 || convertPlanId.value !== ''
)

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
  convertPlanId.value = ''
  convertAmount.value = props.grantableCourses[0]?.display_price ?? 0
  convertForce.value = false
  convertError.value = ''
  showConvertModal.value = true
}

// A warning acknowledged for one course says nothing about the next, and a
// tier belongs to exactly one course.
watch(convertCourseId, () => {
  convertForce.value = false
  convertError.value = ''
  convertPlanId.value = ''
})

// Deal price defaults to the selected course's current display price;
// the admin overrides it with the actual (offline) deal amount.
watch(convertCourseId, (id) => {
  const course = props.grantableCourses.find(c => c.id === Number(id))
  if (course) convertAmount.value = course.display_price
})

// A tier's suggested price is the more specific default when there is one.
watch(convertPlanId, (id) => {
  const plan = convertCoursePlans.value.find(p => p.id === Number(id))
  if (plan && plan.price !== null) convertAmount.value = plan.price
})

const confirmConvert = async () => {
  if (!convertCourseId.value || !convertingLead.value || !convertAmountValid.value) return
  if (!convertPlanValid.value) return
  if (conflictingPurchase.value && !convertForce.value) return
  convertLoading.value = true
  convertError.value = ''
  try {
    const res = await axios.post(`/admin/high-ticket-leads/${convertingLead.value.id}/convert`, {
      course_id: Number(convertCourseId.value),
      course_plan_id: convertPlanId.value === '' ? null : Number(convertPlanId.value),
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
    // 409 belongs next to the checkbox that resolves it, and 422 next to the
    // plan picker that caused it — not in the page banner.
    if (e.response?.status === 409 || e.response?.status === 422) {
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
    met: metFilter.value || undefined,
    consultant: consultantFilter.value || undefined,
  }, { preserveState: true })
}

const formatDateTime = (str) => {
  if (!str) return '-'
  return new Date(str).toLocaleString('zh-TW')
}

// Suppression badge (000 US9) — bounce/complaint facts recorded from Resend webhooks.
const suppressionLabel = (email) => {
  const reason = props.suppressionsByEmail[email]
  if (reason === 'bounce') return '已退信'
  if (reason === 'complaint') return '已投訴'
  return null
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
    <!-- Search, meeting-time & consultant filter -->
    <div class="mb-4 flex flex-col sm:flex-row gap-3">
      <div class="flex-1">
        <input
          v-model="search"
          type="text"
          placeholder="搜尋姓名、Email..."
          class="block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-teal focus:ring-brand-teal sm:text-sm"
        />
      </div>
      <!-- Meeting-time quick filters (US28). By the booked consultation slot,
           not booked_at — the follow-up list is "who did I talk to", and the
           two are routinely a fortnight apart. -->
      <div class="flex items-center gap-2">
        <button
          v-for="preset in metPresets"
          :key="preset.value"
          @click="toggleMet(preset.value)"
          :title="preset.hint"
          class="px-3 py-2 rounded-md border text-sm font-medium cursor-pointer transition-colors"
          :class="metFilter === preset.value
            ? 'bg-brand-teal border-brand-teal text-white hover:bg-brand-teal/90'
            : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50 hover:border-gray-400'"
        >
          {{ preset.label }}
        </button>
      </div>
      <!-- Owner filter (US18). 未指派 is its own option on purpose: every lead
           from before US15 sits there and is otherwise unreachable. -->
      <div class="flex items-center gap-2">
        <select
          v-model="consultantFilter"
          @change="applyFilters()"
          class="block rounded-md border-gray-300 shadow-sm focus:border-brand-teal focus:ring-brand-teal sm:text-sm cursor-pointer hover:border-gray-400"
        >
          <option value="">所有顧問</option>
          <option v-for="person in consultantOptions" :key="person.id" :value="person.id">
            {{ person.nickname || person.email }}
          </option>
          <option value="none">未指派</option>
        </select>
        <button
          v-if="consultantFilter"
          @click="consultantFilter = ''; applyFilters()"
          class="text-gray-400 hover:text-gray-600 cursor-pointer"
          title="清除顧問篩選"
        >
          <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Status filter tabs -->
    <div class="mb-4 flex gap-2 flex-wrap items-center">
      <button
        v-for="tab in tabs"
        :key="tab.value"
        @click="applyFilter(tab.value)"
        class="px-4 py-1.5 rounded-full text-sm font-medium border cursor-pointer transition-colors"
        :class="filters.status === (tab.value || null) || (!filters.status && !tab.value)
          ? tab.tabActive
          : tab.tabIdle"
        :title="tabTitle(tab.value)"
      >
        {{ tab.label }}
        <span v-if="tabMetric(tab.value)" class="ml-1 tabular-nums opacity-80">{{ tabMetric(tab.value) }}</span>
      </button>

      <!-- Deals closed (011 US22). Follows the course/consultant filters, not
           the status tab — clicking into a status must not move the money.
           One line, and shorter than a pill, so the row keeps its height. -->
      <div
        class="lg:ml-auto text-xs text-gray-500 tabular-nums whitespace-nowrap"
        title="成交人數依 Email 去重；金額只計顧問開通（已退款不計）。跟隨上方時間／顧問篩選，不受狀態色塊影響"
      >
        本月
        <span class="font-medium text-gray-900">{{ conversionStats.month.people }}</span> 人
        <span class="font-medium text-gray-900">NT$ {{ Number(conversionStats.month.amount).toLocaleString() }}</span>
        <span class="mx-1.5 text-gray-300">|</span>
        年度
        <span class="font-medium text-gray-900">{{ conversionStats.year.people }}</span> 人
        <span class="font-medium text-gray-900">NT$ {{ Number(conversionStats.year.amount).toLocaleString() }}</span>
      </div>
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
            <th class="hidden lg:table-cell whitespace-nowrap px-4 py-3 text-left">諮詢時段</th>
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
            <!-- The consultation itself, not when the form was sent: this is
                 the column that has to move when a booking is rescheduled.
                 Leftmost of the data columns because that is what the list is
                 now scanned by — the 今日／近 7 日 filters above sort the page
                 into a day's work, and the time is what identifies each row
                 in it. -->
            <td class="hidden lg:table-cell whitespace-nowrap py-4 px-3 text-sm text-gray-600">
              <span v-if="formatSlotRange(lead)">{{ formatSlotRange(lead) }}</span>
              <span v-else class="text-gray-400">—</span>
            </td>
            <td class="whitespace-nowrap py-4 px-3 text-sm text-gray-900">
              <div class="inline-flex items-center gap-1.5">
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

                <!-- 011 US23: how many summaries sit behind this row, and a
                     shortcut straight into the newest one. A sibling of the
                     toggle rather than a child of it — a button inside a button
                     is invalid markup, and @click.stop on a span would leave it
                     unreachable by keyboard. -->
                <button
                  v-if="summarised(lead).length"
                  type="button"
                  class="inline-flex items-center gap-0.5 rounded-full bg-gray-100 px-1.5 py-0.5 text-xs font-normal text-gray-600 cursor-pointer hover:bg-brand-teal/10 hover:text-brand-teal transition-colors"
                  :title="`${summarised(lead).length} 份面談摘要 — 點擊查看最近一份`"
                  @click="openSummary(lead)"
                >
                  <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                  {{ summarised(lead).length }}
                </button>
              </div>
            </td>
            <td class="whitespace-nowrap py-4 px-3 text-sm text-gray-600">
              <div class="flex items-center gap-2">
                <span>{{ lead.email }}</span>
                <span
                  v-if="suppressionLabel(lead.email)"
                  class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-red-100 text-red-700"
                  :title="suppressionLabel(lead.email) === '已退信' ? '此 email 已被標記為永久退信，行銷與交易信皆不寄' : '此 email 已標記為投訴，行銷信不寄'"
                >
                  {{ suppressionLabel(lead.email) }}
                </span>
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

                <!-- Decline + cancel in one action (US27 / FR-138). Only where
                     there is a booking to take apart — without one the 已婉拒
                     square to the left is already the whole job. -->
                <button
                  v-if="hasLiveBooking(lead)"
                  type="button"
                  :disabled="!declineReason || decliningId === lead.id"
                  :title="declineReason
                    ? '婉拒並取消預約：釋出時段、刪除 Zoom 會議並寄出婉拒通知'
                    : '找不到「預約婉拒通知」Email 模板，請先到 Email 模板管理建立'"
                  @click="declineTarget = lead"
                  class="ml-1 flex-shrink-0 rounded border border-rose-300 bg-white px-2 py-[3px] text-xs font-semibold text-rose-600 transition-colors cursor-pointer hover:bg-rose-50 disabled:opacity-40"
                  :class="decliningId === lead.id ? 'disabled:cursor-wait' : 'disabled:cursor-not-allowed'"
                >
                  {{ decliningId === lead.id ? '處理中…' : '婉拒' }}
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
                    :key="sub.course_slug + sub.status"
                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium whitespace-nowrap tabular-nums"
                    :class="{
                      'bg-blue-100 text-blue-800':    sub.status === 'active',
                      'bg-purple-100 text-purple-800': sub.status === 'booked',
                      'bg-green-100 text-green-800':   sub.status === 'completed',
                      'bg-yellow-100 text-yellow-800': sub.status === 'converted',
                      'bg-gray-100 text-gray-500':     sub.status === 'unsubscribed',
                    }"
                    :title="`${sub.course_name}（${dripStatusLabels[sub.status] ?? sub.status}）— 已寄出第 ${sub.emails_sent} 封`"
                  >
                    {{ sub.course_slug }} ({{ sub.emails_sent }})
                  </span>
                </div>
              </template>
              <span v-else class="text-gray-400">—</span>
            </td>
            <td class="hidden lg:table-cell whitespace-nowrap py-4 px-3 text-sm text-gray-600">
              <span v-if="lead.consultant">{{ lead.consultant.nickname || lead.consultant.email }}</span>
              <span v-else class="text-gray-400">—</span>
            </td>
          </tr>

          <!-- Application questionnaire (011 US9) — rows from the old one-step
               form simply have nothing to show. -->
          <tr v-if="openDetailIds.includes(lead.id)" class="bg-gray-50">
            <td colspan="9" class="px-6 py-4 space-y-4">
              <!-- Booking facts first, and never gated: they exist for every
                   lead, questionnaire or not. -->
              <div class="grid gap-3 sm:grid-cols-2 text-sm">
                <div v-for="row in bookingRows(lead)" :key="row.label">
                  <p class="text-xs font-medium text-gray-500">{{ row.label }}</p>
                  <p v-if="row.href" class="mt-0.5">
                    <a :href="row.href" target="_blank" rel="noopener" class="text-brand-teal underline cursor-pointer hover:opacity-70 break-all">
                      {{ row.value }}
                    </a>
                  </p>
                  <p v-else class="mt-0.5 text-gray-800 whitespace-pre-wrap break-words">{{ row.value }}</p>
                </div>
              </div>

              <div v-if="hasApplication(lead)" class="grid gap-3 sm:grid-cols-2 text-sm border-t border-gray-200 pt-4">
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
              <p v-else-if="lead.screened_at" class="text-sm text-gray-400 border-t border-gray-200 pt-4">
                —　通過資格審核後就沒有再往下填，尚未送出申請。
              </p>
              <p v-else class="text-sm text-gray-400 border-t border-gray-200 pt-4">—　這筆預約在申請問卷上線前送出，沒有問卷內容。</p>

              <!-- 011 US24：第一步的五題資格審核。分數只在這裡出現，申請人永遠看不到 -->
              <div class="border-t border-gray-200 pt-4">
                <div v-if="lead.screened_at" class="space-y-3">
                  <div class="flex flex-wrap items-center gap-2">
                    <p class="text-xs font-semibold text-gray-600">資格審核</p>
                    <span class="rounded px-1.5 py-0.5 text-xs font-bold tabular-nums text-gray-700 bg-gray-100">
                      {{ lead.screening_score }}/10
                    </span>
                    <span
                      v-if="screeningTier(lead)"
                      class="rounded px-1.5 py-0.5 text-xs font-semibold"
                      :class="screeningTier(lead).class"
                    >
                      {{ screeningTier(lead).label }}
                    </span>
                    <span v-if="lead.declined_at" class="rounded bg-rose-50 px-1.5 py-0.5 text-xs font-semibold text-rose-700">
                      已自動婉拒
                    </span>
                  </div>

                  <div class="grid gap-3 text-sm sm:grid-cols-2">
                    <div v-for="(row, i) in lead.screening_answers" :key="i">
                      <p class="text-xs font-medium text-gray-500">{{ row.title }}</p>
                      <p class="mt-0.5 text-gray-800 break-words">{{ row.answer }}</p>
                    </div>
                  </div>
                </div>
                <p v-else class="text-sm text-gray-400">—　這筆在資格審核上線前送出，沒有審核紀錄。</p>
              </div>

              <!-- 011 US23：這個 email 的所有面談場次，不只這一筆預約 -->
              <ConsultationNotesPanel :notes="lead.consultation_notes || []" />
            </td>
          </tr>
          </template>

          <tr v-if="leads.data?.length === 0">
            <td colspan="9" class="px-6 py-12 text-center text-gray-500">
              {{ hasAnyFilter ? '沒有符合條件的 Leads' : '尚無預約記錄' }}
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

  <!-- Decline confirmation (US27 / FR-141). Quotes the real template body:
       this mail cannot be taken back, so it has to be readable before the click. -->
  <div
    v-if="declineTarget"
    class="fixed inset-0 z-50 flex items-center justify-center"
  >
    <div class="fixed inset-0 bg-black bg-opacity-40" @click="declineTarget = null" />
    <div class="relative bg-white rounded-lg shadow-xl p-6 w-full max-w-lg mx-4 max-h-[90vh] overflow-y-auto">
      <h3 class="text-lg font-semibold text-gray-900 mb-4">婉拒並取消預約</h3>

      <div class="mb-4 rounded-md border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700 space-y-1">
        <p><span class="text-gray-500">對象：</span>{{ declineTarget.name }}（{{ declineTarget.email }}）</p>
        <p><span class="text-gray-500">原訂時段：</span>{{ formatSlotRange(declineTarget) || '—' }}</p>
      </div>

      <p class="mb-2 text-sm text-gray-600">將寄給對方的通知內容：</p>
      <div
        class="mb-4 rounded-md border border-gray-200 bg-white p-4 text-sm text-gray-700 prose prose-sm max-w-none"
        v-html="marked(declineReason)"
      />

      <p class="mb-5 text-sm text-rose-700">
        送出後會釋出該時段、刪除 Zoom 會議並寄出上面這封信，狀態轉為「已婉拒」。此動作無法復原。
      </p>

      <div class="flex justify-end gap-3">
        <button
          @click="declineTarget = null"
          class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md cursor-pointer hover:bg-gray-50"
        >
          取消
        </button>
        <button
          @click="confirmDecline"
          class="px-4 py-2 text-sm font-medium text-white bg-rose-600 border border-transparent rounded-md cursor-pointer hover:bg-rose-700"
        >
          確認婉拒並寄信
        </button>
      </div>
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

      <!-- Plan selector: only for tiered (multi-plan) courses (011 US21) -->
      <div v-if="convertCoursePlans.length > 0" class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">選擇要開通的方案</label>
        <select
          v-model="convertPlanId"
          class="block w-full rounded-md border-gray-300 shadow-sm focus:ring-brand-teal focus:border-brand-teal text-sm cursor-pointer"
        >
          <option value="" disabled>請選擇方案</option>
          <option v-for="plan in convertCoursePlans" :key="plan.id" :value="plan.id">
            {{ plan.name }}<template v-if="plan.price !== null">（建議價 NT$ {{ Number(plan.price).toLocaleString() }}）</template>
          </option>
        </select>
        <p class="mt-1 text-xs text-gray-500">此課程有多個方案，學員只看得到所選方案包含的小節；日後升級可在「會員管理 → 會員詳情」切換</p>
        <p v-if="!convertPlanValid" class="mt-1 text-xs text-red-600">請選擇方案</p>
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
          :disabled="!convertCourseId || !convertAmountValid || !convertPlanValid || convertLoading || (conflictingPurchase && !convertForce)"
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

  <!-- 姓名旁的場次徽章直接開這一個（FR-121）；展開列裡的面談紀錄面板另有一個實例，
       兩者各自持有狀態，但改動的是同一個 note 物件，所以資料不會分岔 -->
  <ConsultationSummaryModal :show="!!quickNote" :note="quickNote" @close="quickNote = null" />
</template>
