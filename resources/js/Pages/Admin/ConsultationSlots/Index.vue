<script setup>
import { computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import WeekGrid from '@/Components/Admin/ConsultationSlots/WeekGrid.vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  week: { type: Object, required: true },
  range: { type: Object, required: true },
  days: { type: Array, default: () => [] },
})

const page = usePage()
const flash = computed(() => page.props.flash?.success)
const errors = computed(() => Object.values(page.props.errors ?? {}))

const VISIT = { preserveScroll: true, preserveState: false }

function goToWeek(week) {
  router.get('/admin/consultation-slots', { week }, VISIT)
}

function create(payload) {
  router.post('/admin/consultation-slots', payload, VISIT)
}

function release(payload) {
  router.delete('/admin/consultation-slots', { ...VISIT, data: payload })
}

const LEGEND = [
  { label: '未釋出', class: 'bg-white border-gray-300' },
  { label: '可預約', class: 'bg-teal-100 border-teal-300' },
  { label: '暫留中', class: 'bg-amber-100 border-amber-300' },
  { label: '已預約', class: 'bg-indigo-100 border-indigo-300' },
]
</script>

<template>
  <div class="space-y-4">
    <div class="flex items-start justify-between gap-4 flex-wrap">
      <div>
        <h1 class="text-xl font-bold text-gray-900">諮詢時段</h1>
        <p class="mt-1 text-sm text-gray-500">
          1 格 = 15 分鐘。在空白格上拖曳可釋出時段，在已釋出的格上拖曳則收回；已被預約的格不會被動到。
        </p>
      </div>

      <div class="flex items-center gap-2">
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

    <div v-if="flash" class="rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
      {{ flash }}
    </div>

    <div v-for="message in errors" :key="message" class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
      {{ message }}
    </div>

    <div class="flex items-center justify-between gap-4 flex-wrap">
      <p class="text-sm font-semibold text-gray-700 tabular-nums">{{ props.week.label }}</p>
      <div class="flex items-center gap-3 flex-wrap">
        <span v-for="item in LEGEND" :key="item.label" class="flex items-center gap-1.5 text-xs text-gray-600">
          <span class="w-3 h-3 rounded-sm border" :class="item.class" />
          {{ item.label }}
        </span>
      </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-3 sm:p-4">
      <WeekGrid :range="props.range" :days="props.days" @create="create" @release="release" />
    </div>

    <p class="text-xs text-gray-400">
      一場諮詢預設佔 2 格（30 分鐘），使用預約優惠碼者佔 3 格（45 分鐘）。已被預約的時段要先到
      <a href="/admin/high-ticket-leads" class="underline cursor-pointer hover:text-gray-600">Leads 名單</a>
      處理該筆預約才能收回。
    </p>
  </div>
</template>
