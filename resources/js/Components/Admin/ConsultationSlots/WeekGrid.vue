<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  // { start, end, rows: ['08:00', '08:15', …] }
  range: { type: Object, required: true },
  days: { type: Array, required: true },
  consultants: { type: Array, default: () => [] },
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
}

onMounted(() => {
  media = window.matchMedia('(max-width: 639px)')
  syncNarrow(media)
  media.addEventListener('change', syncNarrow)
  window.addEventListener('keydown', onKeydown)
})

onUnmounted(() => {
  media?.removeEventListener('change', syncNarrow)
  window.removeEventListener('keydown', onKeydown)
  window.removeEventListener('pointermove', onMove)
  window.removeEventListener('pointerup', commit)
  window.removeEventListener('pointercancel', commit)
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

function selectBooking(dIndex, booking) {
  if (selected.value?.booking.lead_id === booking.lead_id && !rescheduling.value) {
    clearSelection()
    return
  }

  selected.value = { dIndex, booking }
  rescheduling.value = false
}

function clearSelection() {
  selected.value = null
  rescheduling.value = false
}

function startReschedule() {
  if (selected.value) rescheduling.value = true
}

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
  free: 'bg-teal-100 hover:bg-teal-200 cursor-pointer',
  busy: 'bg-transparent cursor-default',
  past: 'bg-gray-50 cursor-not-allowed',
}

const BLOCK_CLASS = {
  held: 'bg-amber-100 border-amber-300 text-amber-900',
  booked: 'bg-indigo-100 border-indigo-300 text-indigo-900',
}

const STATE_LABEL = { held: '暫留中', booked: '已預約' }

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

    <!-- Left: what the current gesture will do. Right: the selected booking.
         Split rather than one slot so the action panel sits at the top-right of
         the grid, where the eye already is after clicking a block. -->
    <div class="min-h-8 mb-1 flex flex-wrap items-start justify-between gap-2">
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

      <!-- Picking a new time. The buttons live here rather than inside the
           block: a 30-minute booking is 28px tall and holds one line. -->
      <div
        v-if="rescheduling"
        class="flex flex-wrap items-center gap-2 rounded-lg border border-brand-teal bg-brand-teal/10 px-3 py-1.5 text-xs"
      >
        <span class="font-bold text-brand-teal">改期中</span>
        <span class="text-gray-700">
          點選新的開始時間（需 {{ selected.booking.units * 15 }} 分鐘連續空檔）
        </span>
        <button
          type="button"
          class="rounded border border-gray-300 bg-white px-2 py-0.5 text-gray-600 cursor-pointer hover:bg-gray-50 transition"
          @click="clearSelection"
        >
          取消改期（Esc）
        </button>
      </div>

      <!-- Carries everything the block gave up when it shrank to one line. -->
      <div
        v-else-if="selected"
        class="flex flex-wrap items-center gap-x-2 gap-y-1 rounded-lg border border-indigo-300 bg-indigo-50 px-3 py-1.5 text-xs"
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
        <span v-if="selected.booking.state !== 'booked'" class="text-gray-500">
          尚未確認，逾時會自動釋出
        </span>
        <button
          type="button"
          class="rounded border border-gray-300 bg-white px-2 py-0.5 text-gray-600 cursor-pointer hover:bg-gray-50 transition"
          @click="clearSelection"
        >
          關閉
        </button>
      </div>
    </div>

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
                : CELL_CLASS[cellState(dIndex, rIndex)],
              isHour(label) ? 'border-gray-300' : (isHalf(label) ? 'border-gray-200' : 'border-gray-100'),
              inDrag(dIndex, rIndex)
                ? (drag.mode === 'create' ? '!bg-teal-400/60' : '!bg-rose-400/60')
                : '',
            ]"
            :style="{ gridRow: `${rIndex + 1} / span 1`, gridColumn: '1' }"
            :title="visibleDays[dIndex].owners?.[label] ? `${label} · ${visibleDays[dIndex].owners[label]}` : ''"
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
            @click="selectBooking(dIndex, booking)"
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
