<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import WeekGrid from '@/Components/Admin/ConsultationSlots/WeekGrid.vue'
import { colorFor, UNASSIGNED_COLOR } from '@/Components/Admin/ConsultationSlots/consultantPalette.js'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  week: { type: Object, required: true },
  range: { type: Object, required: true },
  days: { type: Array, default: () => [] },
  bonusCodes: { type: String, default: '' },
  consultants: { type: Array, default: () => [] },
  currentUserId: { type: Number, default: null },
  ownerId: { type: Number, default: null },
  unassignedCount: { type: Number, default: 0 },
  canPickConsultant: { type: Boolean, default: false },
  statusCounts: { type: Object, default: () => ({}) },
})

const consultantLabel = (c) => c?.nickname || c?.email || `#${c?.id}`

// Who newly dragged slots belong to (011 US33 / FR-174). Read straight from the
// server-normalised prop rather than kept locally: changing week is a full
// visit, so a local copy would silently reset to the default just when it
// matters most — and a slot opened under the wrong name looks no different.
const owner = computed(() => props.ownerId)

function pickOwner(id) {
  router.get('/admin/consultation-slots', { week: props.week.start, owner: id }, VISIT)
}

const settings = useForm({ bonus_codes: props.bonusCodes })

function saveSettings() {
  settings.put('/admin/consultation-slots/settings', { preserveScroll: true })
}

const page = usePage()
const flash = computed(() => page.props.flash?.success)
const errors = computed(() => Object.values(page.props.errors ?? {}))

// Toast rather than a banner in the flow: every drag produces one, and a block
// appearing above the grid pushed the whole calendar down — so the cell you
// were aiming at next had moved by the time the response came back.
const toast = ref(null)
let toastTimer = null

function showToast(text, tone) {
  clearTimeout(toastTimer)
  toast.value = { text, tone }
  toastTimer = setTimeout(() => { toast.value = null }, 4000)
}

function syncToast() {
  if (errors.value.length > 0) {
    showToast(errors.value[0], 'error')
  } else if (flash.value) {
    showToast(flash.value, 'success')
  }
}

onMounted(syncToast)
watch([flash, errors], syncToast)
onUnmounted(() => clearTimeout(toastTimer))

const VISIT = { preserveScroll: true, preserveState: false }

// `owner` rides along on every navigation — that is the whole point of keeping
// it in the URL rather than in the component (FR-174).
function goToWeek(week) {
  router.get('/admin/consultation-slots', { week, owner: owner.value }, VISIT)
}

function create(payload) {
  router.post('/admin/consultation-slots', { ...payload, consultant_id: owner.value }, VISIT)
}

function assignConsultant({ lead_id, consultant_id }) {
  router.put(`/admin/consultation-slots/bookings/${lead_id}/consultant`, { consultant_id }, VISIT)
}

function release(payload) {
  router.delete('/admin/consultation-slots', { ...VISIT, data: payload })
}

function reschedule({ lead_id, date, start_time }) {
  router.put(`/admin/high-ticket-leads/${lead_id}/booking`, { date, start_time }, VISIT)
}

function cancelBooking({ lead_id }) {
  router.delete(`/admin/high-ticket-leads/${lead_id}/booking`, VISIT)
}

// Counts are site-wide, not scoped to the displayed week (011 US13 / D65).
// "未釋出" has no natural denominator (the future is unbounded) so it stays
// a colour-only legend entry.
const LEGEND = computed(() => [
  { label: '未釋出', class: 'bg-white border-gray-300', count: null },
  // Open slots are coloured by consultant now (FR-177), so this row's swatch
  // would be a lie; the ownership buttons above are its legend.
  { label: '可預約', class: 'bg-gradient-to-r from-sky-200 to-violet-200 border-gray-300', count: props.statusCounts.available },
  { label: '暫留中', class: 'bg-amber-100 border-amber-300', count: props.statusCounts.held },
  { label: '已預約', class: 'bg-indigo-100 border-indigo-300', count: props.statusCounts.booked },
])

// The button row doubles as the colour key for the grid, so every consultant
// appears — a consultant who may not open somebody else's time still has to be
// able to look up whose colour that is (FR-173).
const ownerButtons = computed(() => {
  const buttons = props.consultants.map((c) => ({
    id: c.id,
    label: consultantLabel(c),
    count: c.open_count ?? 0,
    selectable: c.selectable === true,
    colors: colorFor(c.color_index),
  }))

  // Only when there are any: an always-visible 未指派 (0) would advertise a
  // state nothing can produce any more (FR-179).
  if (props.unassignedCount > 0) {
    buttons.push({
      id: null,
      label: '未指派',
      count: props.unassignedCount,
      selectable: false,
      colors: UNASSIGNED_COLOR,
    })
  }

  return buttons
})
</script>

<template>
  <!-- Outside the spaced container on purpose: as a child of `space-y-4` it
       would count as the first element and shift everything below it down by
       one gap the moment it appeared — the very jump it exists to prevent. -->
  <Transition
    enter-active-class="transition duration-150 ease-out"
    enter-from-class="translate-y-2 opacity-0"
    leave-active-class="transition duration-150 ease-in"
    leave-to-class="translate-y-2 opacity-0"
  >
    <div
      v-if="toast"
      class="fixed bottom-6 right-6 z-50 max-w-sm rounded-lg border px-4 py-3 text-sm shadow-lg"
      :class="toast.tone === 'error'
        ? 'bg-red-50 border-red-200 text-red-800'
        : 'bg-green-50 border-green-200 text-green-800'"
    >
      {{ toast.text }}
    </div>
  </Transition>

  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

    <div class="flex items-start justify-between gap-4 flex-wrap">
      <div>
        <h1 class="text-xl font-bold text-gray-900">諮詢時段</h1>
        <p class="mt-1 text-sm text-gray-500">
          1 格 = 15 分鐘。在空白格上拖曳可釋出時段，在已釋出的格上拖曳則收回；已被預約的格不會被動到。
          點一筆已預約的區塊可以改期或取消。
        </p>
      </div>

      <div class="flex items-center gap-2 flex-wrap">
        <button
          type="button"
          class="px-3 py-2 rounded-lg border border-gray-200 text-sm text-gray-600 cursor-pointer hover:bg-gray-50 transition"
          @click="goToWeek(props.week.prev)"
        >
          ← 上一週
        </button>
        <button
          type="button"
          :disabled="props.week.is_current"
          class="px-3 py-2 rounded-lg border border-gray-200 text-sm text-gray-600 cursor-pointer hover:bg-gray-50 transition disabled:opacity-40 disabled:cursor-not-allowed"
          @click="goToWeek(props.week.current)"
        >
          本週
        </button>
        <button
          type="button"
          class="px-3 py-2 rounded-lg border border-gray-200 text-sm text-gray-600 cursor-pointer hover:bg-gray-50 transition"
          @click="goToWeek(props.week.next)"
        >
          下一週 →
        </button>
      </div>
    </div>

    <!-- Ownership picker and colour key in one row (011 US33). The number is
         how much sellable time that consultant still has, site-wide and
         future-only — the same basis as 可預約 in the legend below (FR-175). -->
    <div class="flex items-center gap-2 flex-wrap">
      <span class="text-sm text-gray-600 whitespace-nowrap">時段歸屬</span>
      <button
        v-for="b in ownerButtons"
        :key="b.id ?? 'unassigned'"
        type="button"
        :disabled="!b.selectable"
        class="px-3 py-1.5 rounded-lg border text-sm font-medium transition tabular-nums"
        :class="[
          owner === b.id ? `${b.colors.solid} ring-2` : b.colors.soft,
          b.selectable ? 'cursor-pointer hover:brightness-95' : 'cursor-default opacity-80',
        ]"
        :title="b.selectable ? `新拖曳的時段歸給 ${b.label}` : `${b.label}（你只能開自己的時段）`"
        @click="b.selectable && pickOwner(b.id)"
      >
        {{ b.label }} ({{ b.count }})
      </button>
    </div>

    <div class="flex items-center justify-between gap-4 flex-wrap">
      <p class="text-sm font-semibold text-gray-700 tabular-nums">{{ props.week.label }}</p>
      <div class="flex items-center gap-3 flex-wrap">
        <span v-for="item in LEGEND" :key="item.label" class="flex items-center gap-1.5 text-xs text-gray-600">
          <span class="w-3 h-3 rounded-sm border" :class="item.class" />
          {{ item.label }}<span v-if="item.count !== null" class="tabular-nums">（{{ item.count }}）</span>
        </span>
      </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-3 sm:p-4">
      <WeekGrid
        :range="props.range"
        :days="props.days"
        :consultants="props.consultants"
        :owner-id="owner"
        :can-pick-consultant="props.canPickConsultant"
        @create="create"
        @release="release"
        @reschedule="reschedule"
        @cancel="cancelBooking"
        @assign="assignConsultant"
      />
    </div>

    <form class="bg-white rounded-xl border border-gray-200 p-4 space-y-2" @submit.prevent="saveSettings">
      <div>
        <label class="block text-sm font-semibold text-gray-800">預約優惠碼</label>
        <p class="mt-0.5 text-xs text-gray-500">
          填寫這些碼的申請人，諮詢會從 30 分鐘延長為 45 分鐘（多佔 1 格）。多組請以逗號分隔；比對忽略大小寫與前後空白。留空即所有碼皆無效，一律 30 分鐘。
        </p>
      </div>
      <div class="flex gap-2 flex-wrap sm:flex-nowrap">
        <input
          v-model="settings.bonus_codes"
          type="text"
          placeholder="例：VIP2026, GOLD, 早鳥"
          class="w-full min-w-0 rounded-lg border-gray-300 text-sm focus:border-brand-teal focus:ring-brand-teal"
          :class="{ 'border-red-300': settings.errors.bonus_codes }"
        >
        <button
          type="submit"
          :disabled="settings.processing"
          class="shrink-0 px-4 py-2 rounded-lg bg-brand-teal text-white text-sm font-medium cursor-pointer hover:bg-teal-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {{ settings.processing ? '儲存中…' : '儲存' }}
        </button>
      </div>
      <p v-if="settings.errors.bonus_codes" class="text-sm text-red-600">{{ settings.errors.bonus_codes }}</p>
    </form>

    <p class="text-xs text-gray-400">
      一場諮詢預設佔 2 格（30 分鐘），使用預約優惠碼者佔 3 格（45 分鐘）。已被預約的時段不能直接拖曳收回 ——
      點該區塊選「取消預約」，系統會釋出時段、刪除 Zoom 會議並寄出取消通知與行事曆更新；申請人資料在
      <a href="/admin/high-ticket-leads" class="underline cursor-pointer hover:text-gray-600">Leads 名單</a>。
    </p>
  </div>
</template>
