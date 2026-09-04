<script setup>
import { ref, computed, nextTick, onMounted, onUnmounted } from 'vue'
import { colorFor, UNASSIGNED_COLOR } from './consultantPalette.js'

const props = defineProps({
  // { start, end, rows: ['08:00', '08:15', …] }
  range: { type: Object, required: true },
  days: { type: Array, required: true },
  consultants: { type: Array, default: () => [] },
  ownerId: { type: Number, default: null },
  canPickConsultant: { type: Boolean, default: false },
})

const emit = defineEmits(['create', 'release', 'reschedule', 'cancel', 'assign'])

const consultantLabel = (c) => c?.nickname || c?.email || `#${c?.id}`

// ---- responsive: one column on phones (011 US13) ---------------------------

const isNarrow = ref(false)
const dayIndex = ref(0)
let media = null

function syncNarrow(e) {
  isNarrow.value = e.matches
  if (isNarrow.value) dayIndex.value = Math.max(0, props.days.findIndex(d => d.is_today))
  // The narrow/wide switch swaps which DOM node (if any) the selection panel
  // is anchored to (D66) — closing it is simpler than chasing the new node.
  clearSelection()
}

onMounted(() => {
  media = window.matchMedia('(max-width: 639px)')
  syncNarrow(media)
  media.addEventListener('change', syncNarrow)
  window.addEventListener('keydown', onKeydown)
  window.addEventListener('scroll', updatePanelPos, true)
  window.addEventListener('resize', updatePanelPos)
})

onUnmounted(() => {
  media?.removeEventListener('change', syncNarrow)
  window.removeEventListener('keydown', onKeydown)
  window.removeEventListener('pointermove', onMove)
  window.removeEventListener('pointerup', commit)
  window.removeEventListener('pointercancel', commit)
  window.removeEventListener('scroll', updatePanelPos, true)
  window.removeEventListener('resize', updatePanelPos)
  clearTimeout(copiedTimer)
})

const visibleDays = computed(() =>
  isNarrow.value ? props.days.slice(dayIndex.value, dayIndex.value + 1) : props.days
)

function stepDay(delta) {
  dayIndex.value = Math.min(props.days.length - 1, Math.max(0, dayIndex.value + delta))
}

// ---- per-day lookup tables -------------------------------------------------

const dayState = computed(() =>
  visibleDays.value.map(day => {
    const free = new Set(day.free)
    const busy = new Set()

    for (const b of day.bookings) {
      const from = props.range.rows.indexOf(b.start)
      if (from < 0) continue
      for (let i = 0; i < b.units; i++) busy.add(from + i)
    }

    return { free, busy }
  })
)

function cellState(dIndex, rIndex) {
  const day = visibleDays.value[dIndex]
  if (day.is_past) return 'past'
  const { free, busy } = dayState.value[dIndex]
  if (busy.has(rIndex)) return 'busy'
  return free.has(props.range.rows[rIndex]) ? 'free' : 'empty'
}

// ---- booking selection & reschedule mode (011 US14 / D53) ------------------

// Two-stage rather than dragging the block itself: `pointerdown` on a cell is
// already spoken for by release/reclaim (D45), and stacking a third meaning on
// it would make every mouse-down ambiguous.
const selected = ref(null)
const rescheduling = ref(false)

function selectBooking(dIndex, booking, event) {
  if (selected.value?.booking.lead_id === booking.lead_id && !rescheduling.value) {
    clearSelection()
    return
  }

  selected.value = { dIndex, booking }
  rescheduling.value = false
  if (event?.currentTarget) anchorEl = event.currentTarget
  nextTick(updatePanelPos)
}

function clearSelection() {
  selected.value = null
  rescheduling.value = false
  anchorEl = null
}

// ---- reschedule options (011 US20) ----------------------------------------
//
// The grid draws one week and switching week is a full visit that resets
// reschedule mode (D68), so clicking a target cell can only ever reach the week
// already on screen. This list is how the other weeks become reachable.
const options = ref([])
const optionsLoading = ref(false)
const optionsError = ref('')
const pickedDate = ref('')
const pickedTime = ref('')

const pickedGroup = computed(() => options.value.find((group) => group.value === pickedDate.value))
const timesForPickedDate = computed(() => pickedGroup.value?.times ?? [])

async function startReschedule() {
  if (!selected.value) return

  rescheduling.value = true
  // The banner is a different width/height than the selected-booking panel
  // it replaces, so the anchor point needs recomputing once it has painted.
  nextTick(updatePanelPos)

  // Always refetched, never cached (FR-085): a page that has been open for
  // twenty minutes carries exactly the stale list that produces a 409.
  options.value = []
  optionsError.value = ''
  pickedDate.value = ''
  pickedTime.value = ''
  optionsLoading.value = true

  try {
    const { data } = await window.axios.get(
      `/admin/consultation-slots/reschedule-options/${selected.value.booking.lead_id}`
    )
    options.value = data.slots ?? []
  } catch (e) {
    optionsError.value = e.response?.data?.message || '無法取得可用時段，請重新整理後再試'
  } finally {
    optionsLoading.value = false
    nextTick(updatePanelPos)
  }
}

/** Reschedule via the dropdowns — the grid-click path stays as it is (D76). */
function confirmPickedTime() {
  const booking = selected.value?.booking
  const time = timesForPickedDate.value.find((t) => t.value === pickedTime.value)

  if (!booking || !time) return

  const ok = window.confirm(
    `將 ${booking.name} 的諮詢改期？\n\n`
    + `原時段：${selectedDayLabel.value} ${booking.start}–${booking.end}\n`
    + `新時段：${pickedGroup.value.date} ${time.label}\n\n`
    + '系統會寄出更新通知與行事曆邀請，並同步調整 Zoom 會議時間。'
  )

  if (!ok) return

  // Both halves are already Taipei wall-clock, which is what the endpoint
  // expects (D32) — no timezone maths happens in here.
  emit('reschedule', {
    lead_id: booking.lead_id,
    date: pickedDate.value,
    start_time: time.label,
  })
  clearSelection()
}

// ---- floating action panel (011 US14 / D66) ---------------------------
//
// The panel is wider than a 15-minute cell and has to spill over neighbour
// columns, so it cannot live in the block's normal document flow — and the
// calendar sits inside an overflow-x-auto wrapper, which (per the CSS spec)
// clips the *other* axis too once one axis is non-visible. `position: fixed`
// escapes that entirely; `anchorEl.getBoundingClientRect()` is re-read on
// every scroll/resize while a panel is open (see the window listeners in
// onMounted/onUnmounted) so it keeps tracking the block as the page moves.
let anchorEl = null
const panelRef = ref(null)
const panelPos = ref({ left: 0, top: 0, flip: false })

function updatePanelPos() {
  if (!anchorEl || !(selected.value || rescheduling.value)) return

  const rect = anchorEl.getBoundingClientRect()
  const panelWidth = panelRef.value?.offsetWidth ?? 280
  const half = panelWidth / 2
  const margin = 8

  const left = Math.min(
    Math.max(rect.left + rect.width / 2, half + margin),
    window.innerWidth - half - margin
  )
  // Not enough headroom above the block for the panel to sit above it
  // without running off the top of the viewport — flip below instead.
  const flip = rect.top < 80

  panelPos.value = {
    left,
    top: flip ? rect.bottom + margin : rect.top - margin,
    flip,
  }
}

const panelStyle = computed(() => ({
  left: `${panelPos.value.left}px`,
  top: `${panelPos.value.top}px`,
  transform: `translate(-50%, ${panelPos.value.flip ? '0' : '-100%'})`,
}))

function onKeydown(e) {
  if (e.key === 'Escape') clearSelection()
}

/**
 * Can the selected booking start here? Its own units count as free, because the
 * server releases them before it checks (FR-048) — otherwise nudging a booking
 * by 15 minutes would look impossible.
 */
function canStartHere(dIndex, rIndex) {
  const booking = selected.value?.booking
  if (!booking) return false

  const day = visibleDays.value[dIndex]
  if (day.is_past) return false

  const { free, busy } = dayState.value[dIndex]
  const own = ownRows(dIndex)

  for (let i = 0; i < booking.units; i++) {
    const row = rIndex + i
    if (row >= props.range.rows.length) return false
    if (own.has(row)) continue
    if (busy.has(row)) return false
    if (!free.has(props.range.rows[row])) return false
  }

  return true
}

/** Rows the selected booking currently occupies, in the given column. */
function ownRows(dIndex) {
  const rows = new Set()
  const booking = selected.value?.booking

  if (!booking || selected.value.dIndex !== dIndex) return rows

  const from = props.range.rows.indexOf(booking.start)
  if (from < 0) return rows

  for (let i = 0; i < booking.units; i++) rows.add(from + i)

  return rows
}

function chooseNewStart(dIndex, rIndex) {
  const booking = selected.value?.booking
  if (!booking || !canStartHere(dIndex, rIndex)) return

  const day = visibleDays.value[dIndex]
  const startTime = props.range.rows[rIndex]
  const endTime = props.range.rows[rIndex + booking.units] ?? props.range.end

  const ok = window.confirm(
    `將 ${booking.name} 的諮詢改期？\n\n`
    + `原時段：${selectedDayLabel.value} ${booking.start}–${booking.end}\n`
    + `新時段：${day.label}（週${day.weekday}） ${startTime}–${endTime}\n\n`
    + '系統會寄出更新通知與行事曆邀請，並同步調整 Zoom 會議時間。'
  )

  if (!ok) return

  emit('reschedule', { lead_id: booking.lead_id, date: day.date, start_time: startTime })
  clearSelection()
}

function confirmCancel() {
  const booking = selected.value?.booking
  if (!booking) return

  const ok = window.confirm(
    `取消 ${booking.name} 的諮詢？\n\n`
    + `時段：${selectedDayLabel.value} ${booking.start}–${booking.end}\n\n`
    + '時段會釋出、Zoom 會議會被刪除，並寄出取消通知與行事曆更新。此操作無法復原。'
  )

  if (!ok) return

  emit('cancel', { lead_id: booking.lead_id })
  clearSelection()
}

const selectedDayLabel = computed(() => {
  const day = visibleDays.value[selected.value?.dIndex]
  return day ? `${day.label}（週${day.weekday}）` : ''
})

function isSelected(booking) {
  return selected.value?.booking.lead_id === booking.lead_id
}

/**
 * Blocks are placed by grid-row so a 30-minute booking is one box, not two.
 *
 * gridColumn is explicit and must stay that way: the cell for the same row
 * already sits in column 1, and an auto-placed item whose slot is taken does
 * not overlap — it makes an implicit column 2 and renders there, which puts the
 * block outside the day entirely. Pinning both to column 1 is what lets the
 * block (z-10) sit on top of the cells, which is the whole design.
 */
function blockStyle(booking) {
  const from = props.range.rows.indexOf(booking.start)
  return { gridRow: `${from + 1} / span ${booking.units}`, gridColumn: '1' }
}

// ---- drag ------------------------------------------------------------------

// The cell you start on decides what the gesture does (D45): empty → release
// availability, already-available → take it back. Occupied and past cells
// cannot start anything, but dragging *over* them is fine — they are skipped
// server-side rather than aborting the whole range.
const drag = ref(null)

function startDrag(dIndex, rIndex) {
  // While picking a new time the grid is a target list, not a canvas.
  if (rescheduling.value) {
    chooseNewStart(dIndex, rIndex)
    return
  }

  const state = cellState(dIndex, rIndex)
  if (state === 'past' || state === 'busy') return

  drag.value = {
    dIndex,
    mode: state === 'free' ? 'release' : 'create',
    from: rIndex,
    to: rIndex,
  }

  window.addEventListener('pointermove', onMove)
  window.addEventListener('pointerup', commit)
  window.addEventListener('pointercancel', commit)
}

/**
 * Hit-test on every move rather than listening for pointerenter on each cell:
 * a touch pointer is captured by the element it started on, so the cells it
 * slides over never fire enter and a phone drag would silently select one row.
 * elementFromPoint behaves identically for mouse and touch, so there is one
 * code path instead of two.
 */
function onMove(e) {
  if (!drag.value) return

  const el = document.elementFromPoint(e.clientX, e.clientY)
  if (!el?.dataset?.row) return

  extendDrag(Number(el.dataset.day), Number(el.dataset.row))
}

function extendDrag(dIndex, rIndex) {
  // Confining a drag to its starting column keeps the emitted payload a single
  // date; a rectangle across days would be a different feature.
  if (!drag.value || drag.value.dIndex !== dIndex) return
  drag.value.to = rIndex
}

function commit() {
  window.removeEventListener('pointermove', onMove)
  window.removeEventListener('pointerup', commit)
  window.removeEventListener('pointercancel', commit)

  const current = drag.value
  drag.value = null
  if (!current) return

  const from = Math.min(current.from, current.to)
  const to = Math.max(current.from, current.to)

  emit(current.mode, {
    date: visibleDays.value[current.dIndex].date,
    start_time: props.range.rows[from],
    end_time: props.range.rows[to + 1] ?? props.range.end,
  })
}

/** First and last selected row, in drawing order regardless of drag direction. */
const dragBounds = computed(() => {
  const d = drag.value
  if (!d) return null
  return { from: Math.min(d.from, d.to), to: Math.max(d.from, d.to) }
})

function inDrag(dIndex, rIndex) {
  const b = dragBounds.value
  return !!b && drag.value.dIndex === dIndex && rIndex >= b.from && rIndex <= b.to
}

const dragLabel = computed(() => {
  const b = dragBounds.value
  if (!b) return { range: '', action: '', minutes: 0 }

  const units = b.to - b.from + 1
  const day = visibleDays.value[drag.value.dIndex]

  return {
    create: drag.value.mode === 'create',
    text: `${day.label}（週${day.weekday}）${props.range.rows[b.from]} – ${props.range.rows[b.to + 1] ?? props.range.end}`,
    action: drag.value.mode === 'create' ? '釋出時段' : '收回時段',
    minutes: units * 15,
  }
})

// ---- styling ---------------------------------------------------------------

const CELL_CLASS = {
  empty: 'bg-white hover:bg-brand-teal/10 cursor-pointer',
  // `free` is not here: an open slot is coloured by whose it is (011 US33 /
  // FR-177). The other three states keep their own colour — a 14px cell cannot
  // carry both "can this be sold" and "whose is it" on the same axis.
  busy: 'bg-transparent cursor-default',
  past: 'bg-gray-50 cursor-not-allowed',
}

// id → { label, colors }. Built once per roster rather than per cell; the grid
// asks this question 364 times a week.
const consultantById = computed(() => {
  const map = new Map()

  for (const c of props.consultants) {
    map.set(c.id, { label: consultantLabel(c), colors: colorFor(c.color_index) })
  }

  return map
})

const ownerOf = (id) => consultantById.value.get(id) ?? { label: '未指派', colors: UNASSIGNED_COLOR }

/** Colour of the cell being dragged out — whose time this is about to become. */
const dragCreateClass = computed(() => ownerOf(props.ownerId).colors.drag)

function freeCellClass(dIndex, rIndex) {
  return ownerOf(visibleDays.value[dIndex].owners?.[props.range.rows[rIndex]]).colors.cell
}

function ownerTitle(dIndex, label) {
  const id = visibleDays.value[dIndex].owners?.[label]

  return id === undefined ? '' : `${label} · ${ownerOf(id).label}`
}

const BLOCK_CLASS = {
  held: 'bg-amber-100 border-amber-300 text-amber-900',
  booked: 'bg-indigo-100 border-indigo-300 text-indigo-900',
}

const STATE_LABEL = { held: '暫留中', booked: '已預約' }

// ---- email picking (011 US17) ----------------------------------------------
//
// Everything here is local state over props the grid already has: the block
// payload carries `email`, so pulling the addresses out is a front-end job and
// needs no request (D67). Selection resets on a week change because switching
// weeks is a full Inertia visit — deliberately, so a copy can never include
// people who are not on screen (D68).

const pickedLeadIds = ref(new Set())
const copiedCount = ref(0)
const manualCopyText = ref('')
const manualCopyInput = ref(null)
let copiedTimer = null

/** Booked and held blocks in reading order — day, then start time. */
const pickableBookings = computed(() =>
  visibleDays.value.flatMap(day =>
    day.bookings.filter(b => b.email && (b.state === 'booked' || b.state === 'held'))
  )
)

const pickedCount = computed(() =>
  pickableBookings.value.filter(b => pickedLeadIds.value.has(b.lead_id)).length
)

function isPicked(booking) {
  return pickedLeadIds.value.has(booking.lead_id)
}

function togglePick(booking) {
  // Reassigning the Set keeps the computed reactive — Set mutation alone does not.
  const next = new Set(pickedLeadIds.value)
  next.has(booking.lead_id) ? next.delete(booking.lead_id) : next.add(booking.lead_id)
  pickedLeadIds.value = next
  manualCopyText.value = ''
}

function clearPicks() {
  pickedLeadIds.value = new Set()
  manualCopyText.value = ''
}

/** `a@x.com, b@y.com` — the separator a mail client splits recipients on. */
function pickedEmails() {
  const seen = new Set()
  for (const b of pickableBookings.value) {
    if (pickedLeadIds.value.has(b.lead_id)) seen.add(b.email)
  }
  return [...seen].join(', ')
}

async function copyEmails() {
  const text = pickedEmails()
  if (!text) return

  try {
    // Only available in a secure context: localhost counts, a LAN IP does not —
    // so this throws exactly when someone tests from a phone on the office
    // network, and a silent failure there is indistinguishable from a dead
    // button (FR-072).
    await navigator.clipboard.writeText(text)
    manualCopyText.value = ''
    copiedCount.value = new Set(text.split(', ')).size
    clearTimeout(copiedTimer)
    copiedTimer = setTimeout(() => { copiedCount.value = 0 }, 2000)
  } catch {
    manualCopyText.value = text
    nextTick(() => manualCopyInput.value?.select())
  }
}

function leadUrl(email) {
  return `/admin/high-ticket-leads?search=${encodeURIComponent(email)}`
}

/** Hours are the anchor; half hours get a fainter rule and a fainter label. */
function isHour(label) {
  return label.endsWith(':00')
}

function isHalf(label) {
  return label.endsWith(':30')
}
</script>

<template>
  <div>
    <!-- Phone: one day at a time -->
    <div v-if="isNarrow" class="flex items-center justify-between mb-3">
      <button
        type="button"
        :disabled="dayIndex === 0"
        class="px-3 py-2 rounded-lg border border-gray-200 text-sm text-gray-600 cursor-pointer hover:bg-gray-50 transition disabled:opacity-40 disabled:cursor-not-allowed"
        @click="stepDay(-1)"
      >
        ←
      </button>
      <p class="text-sm font-semibold text-gray-900">
        {{ visibleDays[0]?.label }}（週{{ visibleDays[0]?.weekday }}）
      </p>
      <button
        type="button"
        :disabled="dayIndex === days.length - 1"
        class="px-3 py-2 rounded-lg border border-gray-200 text-sm text-gray-600 cursor-pointer hover:bg-gray-50 transition disabled:opacity-40 disabled:cursor-not-allowed"
        @click="stepDay(1)"
      >
        →
      </button>
    </div>

    <!-- What the current drag gesture will do. The selected-booking panel used
         to live here too; it now floats above the block itself (D66). -->
    <div class="min-h-8 mb-1">
      <div class="min-w-0">
        <div
          v-if="drag"
          class="inline-flex items-center gap-2 rounded-lg border px-3 py-1.5 text-xs font-medium"
          :class="dragLabel.create
            ? 'bg-teal-50 border-teal-300 text-teal-800'
            : 'bg-rose-50 border-rose-300 text-rose-800'"
        >
          <span class="font-bold">{{ dragLabel.action }}</span>
          <span class="tabular-nums">{{ dragLabel.text }}</span>
          <span class="opacity-70">{{ dragLabel.minutes }} 分鐘</span>
        </div>

        <p v-else class="text-xs text-gray-400">
          在格線上按住並拖曳以選取時段範圍；點一筆預約可改期或取消。
        </p>
      </div>
    </div>

    <!-- Pick applicants off the calendar and take their addresses elsewhere
         (US17). Deliberately does not send anything — batch mail has its own
         path through the template system (D69). -->
    <div v-if="pickableBookings.length" class="mb-2 flex flex-wrap items-center gap-2">
      <span class="text-xs text-gray-500 tabular-nums">已選 {{ pickedCount }} 筆</span>
      <button
        type="button"
        :disabled="pickedCount === 0"
        class="px-3 py-1.5 rounded-lg border border-gray-200 text-xs text-gray-600 cursor-pointer hover:bg-gray-50 transition disabled:opacity-40 disabled:cursor-not-allowed"
        @click="copyEmails"
      >
        {{ copiedCount ? `已複製 ${copiedCount} 筆` : '複製 Email' }}
      </button>
      <button
        v-if="pickedCount"
        type="button"
        class="text-xs text-gray-400 cursor-pointer hover:text-gray-600 transition"
        @click="clearPicks"
      >
        清除
      </button>
    </div>

    <!-- Clipboard refused (not a secure context, or permission denied): hand the
         string over so the copy is still one keystroke away (FR-072). -->
    <div v-if="manualCopyText" class="mb-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2">
      <p class="text-xs text-amber-800">無法自動複製，請按 Ctrl/Cmd+C 複製下方地址：</p>
      <input
        ref="manualCopyInput"
        :value="manualCopyText"
        readonly
        class="mt-1 block w-full rounded border-amber-300 bg-white px-2 py-1 text-xs text-gray-700"
        @focus="$event.target.select()"
      >
    </div>

    <!-- Floating above the selected block (D66): position: fixed, tracked via
         getBoundingClientRect() on scroll/resize so it stays pinned to the
         block instead of scrolling out of view with the rest of the page. -->
    <Teleport to="body">
      <div
        v-if="rescheduling || selected"
        ref="panelRef"
        class="fixed z-40"
        :style="panelStyle"
      >
        <!-- Picking a new time. The buttons live here rather than inside the
             block: a 30-minute booking is 28px tall and holds one line. -->
        <div
          v-if="rescheduling"
          class="w-[19rem] max-w-[calc(100vw-1rem)] rounded-lg border border-brand-teal bg-brand-teal/10 px-3 py-2 text-xs shadow-lg"
        >
          <div class="flex flex-wrap items-center gap-2">
            <span class="font-bold text-brand-teal">改期中</span>
            <span class="text-gray-700">需 {{ selected.booking.units * 15 }} 分鐘連續空檔</span>
          </div>

          <!-- The dropdowns exist because the grid only ever draws one week and
               changing week resets this mode (US20). Same booking, same
               endpoint as clicking a cell — only the way of naming the target
               differs, so both paths stay (D76). -->
          <p v-if="optionsLoading" class="mt-1.5 text-gray-500">讀取可用時段…</p>
          <p v-else-if="optionsError" class="mt-1.5 text-rose-700">{{ optionsError }}</p>
          <p v-else-if="options.length === 0" class="mt-1.5 text-gray-600">
            未來沒有可用時段，請先在週曆上釋出時段再改期。
          </p>
          <div v-else class="mt-1.5 flex flex-wrap items-center gap-1.5">
            <select
              v-model="pickedDate"
              class="rounded border-gray-300 py-0.5 text-xs cursor-pointer focus:border-brand-teal focus:ring-brand-teal"
              @change="pickedTime = ''"
            >
              <option value="">選日期</option>
              <option v-for="group in options" :key="group.value" :value="group.value">
                {{ group.date }}
              </option>
            </select>
            <select
              v-model="pickedTime"
              :disabled="!pickedDate"
              class="rounded border-gray-300 py-0.5 text-xs cursor-pointer focus:border-brand-teal focus:ring-brand-teal disabled:cursor-not-allowed disabled:bg-gray-100"
            >
              <option value="">選時間</option>
              <option v-for="time in timesForPickedDate" :key="time.value" :value="time.value">
                {{ time.label }}
              </option>
            </select>
            <button
              type="button"
              :disabled="!pickedTime"
              class="rounded bg-brand-teal px-2 py-0.5 font-medium text-white cursor-pointer hover:bg-teal-700 transition disabled:opacity-40 disabled:cursor-not-allowed"
              @click="confirmPickedTime"
            >
              確認改期
            </button>
          </div>

          <div class="mt-1.5 flex items-center justify-between gap-2 text-gray-600">
            <span>或直接在格線上點選新的開始時間</span>
            <button
              type="button"
              class="rounded border border-gray-300 bg-white px-2 py-0.5 text-gray-600 cursor-pointer hover:bg-gray-50 transition"
              @click="clearSelection"
            >
              取消改期（Esc）
            </button>
          </div>
        </div>

        <!-- Carries everything the block gave up when it shrank to one line. -->
        <div
          v-else-if="selected"
          class="inline-flex flex-wrap items-center gap-x-2 gap-y-1 rounded-lg border border-indigo-300 bg-indigo-50 px-3 py-1.5 text-xs shadow-lg"
        >
          <span class="font-semibold text-indigo-900">{{ selected.booking.name }}</span>
          <span class="tabular-nums text-indigo-800">
            {{ selectedDayLabel }} {{ selected.booking.start }}–{{ selected.booking.end }}
          </span>
          <span class="text-indigo-700 opacity-80">{{ STATE_LABEL[selected.booking.state] }}</span>
          <a
            v-if="selected.booking.email"
            :href="leadUrl(selected.booking.email)"
            class="text-indigo-700 underline cursor-pointer hover:opacity-70"
          >
            名單
          </a>
          <a
            v-if="selected.booking.zoom_join_url"
            :href="selected.booking.zoom_join_url"
            target="_blank"
            rel="noopener"
            class="font-medium text-indigo-700 underline cursor-pointer hover:opacity-70"
          >
            Zoom
          </a>
          <span v-if="selected.booking.held_until" class="text-amber-700">
            保留至 {{ selected.booking.held_until }}
          </span>
          <label v-if="canPickConsultant" class="flex items-center gap-1 text-indigo-800">
            顧問
            <select
              :value="selected.booking.consultant_id ?? ''"
              class="rounded border-indigo-300 py-0 text-xs cursor-pointer focus:border-brand-teal focus:ring-brand-teal"
              @change="emit('assign', { lead_id: selected.booking.lead_id, consultant_id: $event.target.value || null })"
            >
              <option value="">未指派</option>
              <option v-for="c in consultants" :key="c.id" :value="c.id">{{ consultantLabel(c) }}</option>
            </select>
          </label>
          <span v-else-if="selected.booking.consultant" class="text-indigo-800">
            顧問：{{ selected.booking.consultant }}
          </span>
          <span v-if="selected.booking.state !== 'booked'" class="text-gray-500">
            尚未確認，逾時會自動釋出
          </span>

          <!-- One flex item rather than three loose buttons: with the outer
               row wrapping button-by-button, "改期" could land on line 1 and
               "取消預約" / "關閉" on line 2, splitting a set of actions that
               belong together. Grouped, they wrap onto the next line as a
               single unit instead of breaking mid-group. -->
          <div class="flex items-center gap-2">
            <button
              type="button"
              :disabled="selected.booking.state !== 'booked'"
              class="rounded bg-brand-teal px-2 py-0.5 font-medium text-white cursor-pointer hover:bg-teal-700 transition disabled:opacity-40 disabled:cursor-not-allowed"
              @click="startReschedule"
            >
              改期
            </button>
            <button
              type="button"
              :disabled="selected.booking.state !== 'booked'"
              class="rounded bg-rose-600 px-2 py-0.5 font-medium text-white cursor-pointer hover:bg-rose-700 transition disabled:opacity-40 disabled:cursor-not-allowed"
              @click="confirmCancel"
            >
              取消預約
            </button>
            <button
              type="button"
              class="rounded border border-gray-300 bg-white px-2 py-0.5 text-gray-600 cursor-pointer hover:bg-gray-50 transition"
              @click="clearSelection"
            >
              關閉
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <div class="overflow-x-auto">
      <div
        class="grid w-fit mx-auto select-none border border-gray-200"
        :style="{ gridTemplateColumns: `2.75rem repeat(${visibleDays.length}, ${isNarrow ? '1fr' : '6.5rem'})` }"
      >
        <!-- Header row -->
        <div class="sticky top-0 z-20 bg-gray-50 border-b border-gray-200" />
        <div
          v-for="day in visibleDays"
          :key="`h-${day.date}`"
          class="sticky top-0 z-20 bg-gray-50 border-b border-l border-gray-200 px-1 py-1 text-center"
        >
          <p class="text-[10px] leading-none text-gray-400">週{{ day.weekday }}</p>
          <p
            class="mt-0.5 text-xs font-semibold leading-none"
            :class="day.is_today ? 'text-brand-teal' : (day.is_past ? 'text-gray-300' : 'text-gray-900')"
          >
            {{ day.label }}
          </p>
        </div>

        <!-- Time gutter. Same row track as the day columns, and each label is
             lifted onto its gridline with a transform rather than a negative
             margin — a negative margin on every row would compress the whole
             column and the labels would drift further out of step the further
             down you look. py-2 leaves room for the half-row overhang at both
             ends. touch-action stays default here so the page can still be
             scrolled on a phone, where every cell swallows the gesture. -->
        <div
          class="relative grid grid-cols-1 py-2"
          :style="{ gridTemplateRows: `repeat(${range.rows.length}, 0.875rem)` }"
        >
          <div v-for="label in range.rows" :key="`t-${label}`" class="relative">
            <span
              v-if="isHour(label) || isHalf(label)"
              class="absolute right-1.5 top-0 -translate-y-1/2 leading-none tabular-nums"
              :class="isHour(label)
                ? 'text-[10px] font-semibold text-gray-600'
                : 'text-[9px] text-gray-300'"
            >
              {{ label }}
            </span>
          </div>
          <!-- Closes the grid: without it the last labelled hour is 21:00 and
               the four rows below it look like they run past the end. -->
          <span class="absolute right-1.5 bottom-2 translate-y-1/2 text-[10px] font-semibold leading-none text-gray-600 tabular-nums">
            {{ range.end }}
          </span>
        </div>

        <!-- One grid per day: cells underneath, booking blocks on top -->
        <div
          v-for="(day, dIndex) in visibleDays"
          :key="day.date"
          class="relative grid grid-cols-1 border-l border-gray-200 py-2"
          :style="{ gridTemplateRows: `repeat(${range.rows.length}, 0.875rem)` }"
        >
          <!-- border-t, not border-b: the line belongs to the boundary the
               label names. On the bottom edge every hour rule would sit 15
               minutes below its own number. -->
          <div
            v-for="(label, rIndex) in range.rows"
            :key="`${day.date}-${label}`"
            class="border-t transition-colors touch-none"
            :class="[
              rescheduling
                ? (canStartHere(dIndex, rIndex)
                  ? 'bg-brand-teal/20 hover:bg-brand-teal/50 cursor-pointer'
                  : 'bg-gray-100 cursor-not-allowed')
                : (cellState(dIndex, rIndex) === 'free'
                  ? freeCellClass(dIndex, rIndex)
                  : CELL_CLASS[cellState(dIndex, rIndex)]),
              isHour(label) ? 'border-gray-300' : (isHalf(label) ? 'border-gray-200' : 'border-gray-100'),
              inDrag(dIndex, rIndex)
                ? (drag.mode === 'create' ? dragCreateClass : '!bg-rose-400/60')
                : '',
            ]"
            :style="{ gridRow: `${rIndex + 1} / span 1`, gridColumn: '1' }"
            :title="ownerTitle(dIndex, label)"
            :data-day="dIndex"
            :data-row="rIndex"
            @pointerdown.prevent="startDrag(dIndex, rIndex)"
          />

          <!-- Faded while picking a new time so the selectable cells read as
               the foreground; the selected one stays lit as the reference. -->
          <div
            v-for="booking in day.bookings"
            :key="`${day.date}-${booking.start}-${booking.lead_id}`"
            class="z-10 m-px flex min-w-0 items-center overflow-hidden rounded border px-1 text-[10px] leading-none cursor-pointer transition"
            :class="[
              BLOCK_CLASS[booking.state],
              isSelected(booking) ? 'ring-2 ring-brand-teal' : 'hover:brightness-95',
              rescheduling && !isSelected(booking) ? 'opacity-40' : '',
              // Blocks sit above the cells, so while picking they would swallow
              // the click on any row they cover — including the booking's own,
              // which is exactly where a 15-minute nudge has to land.
              rescheduling ? 'pointer-events-none' : '',
            ]"
            :style="blockStyle(booking)"
            :title="`${booking.start}–${booking.end} ${booking.name}（${STATE_LABEL[booking.state]}）`"
            @click="selectBooking(dIndex, booking, $event)"
          >
            <!-- Picking a recipient must not open the panel the rest of the
                 block opens (US17); the click stops here. -->
            <input
              v-if="booking.email"
              type="checkbox"
              class="mr-1 h-3 w-3 shrink-0 cursor-pointer accent-teal-600"
              :checked="isPicked(booking)"
              :title="`選取 ${booking.name} 的 Email`"
              @click.stop="togglePick(booking)"
            >
            <!-- Name only. A 30-minute block is 28px tall, so anything more
                 clips; the time is already implied by where the block sits and
                 how tall it is, and everything else lives in the panel you get
                 by clicking. -->
            <span class="truncate font-semibold">{{ booking.name }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
