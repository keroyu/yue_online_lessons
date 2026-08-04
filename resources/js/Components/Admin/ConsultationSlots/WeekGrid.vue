<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  // { start, end, rows: ['08:00', '08:15', …] }
  range: { type: Object, required: true },
  days: { type: Array, required: true },
})

const emit = defineEmits(['create', 'release'])

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
})

onUnmounted(() => {
  media?.removeEventListener('change', syncNarrow)
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

/** Blocks are placed by grid-row so a 30-minute booking is one box, not two. */
function blockStyle(booking) {
  const from = props.range.rows.indexOf(booking.start)
  return { gridRow: `${from + 1} / span ${booking.units}` }
}

// ---- drag ------------------------------------------------------------------

// The cell you start on decides what the gesture does (D45): empty → release
// availability, already-available → take it back. Occupied and past cells
// cannot start anything, but dragging *over* them is fine — they are skipped
// server-side rather than aborting the whole range.
const drag = ref(null)

function startDrag(dIndex, rIndex) {
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

    <!-- Readout of the gesture in progress. Kept out of the grid: inside it,
         a one-cell selection is 20px tall and the text has nowhere to go. -->
    <div class="h-8 mb-1 flex items-center">
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
      <p v-else class="text-xs text-gray-400">在格線上按住並拖曳以選取時段範圍。</p>
    </div>

    <div class="overflow-x-auto">
      <div
        class="grid min-w-full select-none"
        :style="{ gridTemplateColumns: `3.5rem repeat(${visibleDays.length}, minmax(5.5rem, 1fr))` }"
      >
        <!-- Header row -->
        <div class="sticky top-0 z-20 bg-white border-b border-gray-200" />
        <div
          v-for="day in visibleDays"
          :key="`h-${day.date}`"
          class="sticky top-0 z-20 bg-white border-b border-l border-gray-200 px-2 py-2 text-center"
        >
          <p class="text-xs text-gray-400">週{{ day.weekday }}</p>
          <p
            class="text-sm font-semibold"
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
          class="relative grid border-r border-gray-200 py-2"
          :style="{ gridTemplateRows: `repeat(${range.rows.length}, 1.25rem)` }"
        >
          <div v-for="label in range.rows" :key="`t-${label}`" class="relative">
            <span
              v-if="isHour(label) || isHalf(label)"
              class="absolute right-2 top-0 -translate-y-1/2 leading-none tabular-nums"
              :class="isHour(label)
                ? 'text-[11px] font-semibold text-gray-600'
                : 'text-[10px] text-gray-300'"
            >
              {{ label }}
            </span>
          </div>
          <!-- Closes the grid: without it the last labelled hour is 21:00 and
               the four rows below it look like they run past the end. -->
          <span class="absolute right-2 bottom-2 translate-y-1/2 text-[11px] font-semibold leading-none text-gray-600 tabular-nums">
            {{ range.end }}
          </span>
        </div>

        <!-- One grid per day: cells underneath, booking blocks on top -->
        <div
          v-for="(day, dIndex) in visibleDays"
          :key="day.date"
          class="relative grid border-l border-gray-200 py-2"
          :style="{ gridTemplateRows: `repeat(${range.rows.length}, 1.25rem)` }"
        >
          <!-- border-t, not border-b: the line belongs to the boundary the
               label names. On the bottom edge every hour rule would sit 15
               minutes below its own number. -->
          <div
            v-for="(label, rIndex) in range.rows"
            :key="`${day.date}-${label}`"
            class="border-t transition-colors touch-none"
            :class="[
              CELL_CLASS[cellState(dIndex, rIndex)],
              isHour(label) ? 'border-gray-300' : (isHalf(label) ? 'border-gray-200' : 'border-gray-100'),
              rIndex === range.rows.length - 1 ? 'border-b border-b-gray-300' : '',
              inDrag(dIndex, rIndex)
                ? (drag.mode === 'create' ? '!bg-teal-400/60' : '!bg-rose-400/60')
                : '',
            ]"
            :style="{ gridRow: `${rIndex + 1} / span 1` }"
            :data-day="dIndex"
            :data-row="rIndex"
            @pointerdown.prevent="startDrag(dIndex, rIndex)"
          />

          <div
            v-for="booking in day.bookings"
            :key="`${day.date}-${booking.start}-${booking.lead_id}`"
            class="z-10 m-px rounded border px-1.5 py-1 overflow-hidden text-[11px] leading-tight"
            :class="BLOCK_CLASS[booking.state]"
            :style="blockStyle(booking)"
          >
            <p class="font-semibold tabular-nums">{{ booking.start }}–{{ booking.end }}</p>
            <a
              v-if="booking.email"
              :href="leadUrl(booking.email)"
              class="block truncate underline cursor-pointer hover:opacity-70"
            >
              {{ booking.name }}
            </a>
            <p v-if="booking.state === 'held'" class="truncate opacity-80">
              保留至 {{ booking.held_until }}
            </p>
            <a
              v-if="booking.zoom_join_url"
              :href="booking.zoom_join_url"
              target="_blank"
              rel="noopener"
              class="block truncate font-medium underline cursor-pointer hover:opacity-70"
            >
              Zoom
            </a>
            <p v-else class="truncate opacity-70">{{ STATE_LABEL[booking.state] }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
