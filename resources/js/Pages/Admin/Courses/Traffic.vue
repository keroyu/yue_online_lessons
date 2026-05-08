<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  course: { type: Object, required: true },
  filters: { type: Object, required: true },
  traffic: { type: Object, required: true },
})

const currentDays = computed(() => props.filters.days)

const trackedPct = computed(() => {
  if (!props.traffic.total_orders) return 0
  return Math.round((props.traffic.tracked_orders / props.traffic.total_orders) * 100)
})

const dayPresets = [
  { label: '最近 7 天', value: 7 },
  { label: '最近 30 天', value: 30 },
  { label: '最近 90 天', value: 90 },
  { label: '全部', value: null },
]

function setDays(days) {
  router.get(`/admin/courses/${props.course.id}/traffic`, days ? { days } : {}, { preserveState: false })
}

const viewMode = ref('source')

function classifyChannel(row) {
  if (row.gclid || row.fbclid || row.ttclid) return '付費廣告'
  const src = (row.utm_source || '').toLowerCase()
  if (/instagram|ig|facebook|fb|threads|twitter|^x$/.test(src)) return '社群'
  if (/google|bing|yahoo|duckduckgo/.test(src)) return '搜尋引擎'
  if (/email|newsletter|edm|mailchimp|resend/.test(src)) return '電子報'
  if (/youtube|tiktok|vimeo/.test(src)) return '影音'
  if (src || row.referrer_domain) return '其他'
  return '(直接造訪)'
}

const groupedSources = computed(() => {
  const groups = {}
  for (const row of props.traffic.sources) {
    const ch = classifyChannel(row)
    if (!groups[ch]) groups[ch] = { channel: ch, order_count: 0, revenue: 0 }
    groups[ch].order_count += row.order_count
    groups[ch].revenue += row.revenue
  }
  return Object.values(groups).sort((a, b) => b.order_count - a.order_count)
})

const exportUrl = computed(() => {
  const base = `/admin/courses/${props.course.id}/traffic/export`
  return currentDays.value ? `${base}?days=${currentDays.value}` : base
})
</script>

<template>
  <div class="max-w-5xl mx-auto px-4 py-6">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-xl font-bold text-gray-900">連結來源追蹤</h1>
        <p class="text-sm text-gray-500 mt-1">{{ course.name }}</p>
      </div>
      <a :href="exportUrl" class="px-3 py-1.5 text-sm bg-teal-600 text-white rounded hover:bg-teal-700">
        匯出 CSV
      </a>
    </div>

    <!-- Time preset buttons -->
    <div class="flex gap-2 mb-6">
      <button
        v-for="p in dayPresets"
        :key="p.label"
        @click="setDays(p.value)"
        :class="[
          'px-3 py-1.5 text-sm rounded border',
          currentDays === p.value
            ? 'bg-indigo-600 text-white border-indigo-600'
            : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50',
        ]"
      >
        {{ p.label }}
      </button>
    </div>

    <!-- Summary cards -->
    <div class="grid grid-cols-2 gap-4 mb-6">
      <div class="bg-white rounded-lg border border-gray-200 p-4">
        <p class="text-sm text-gray-500">總訂單數</p>
        <p class="text-2xl font-bold text-gray-900">{{ traffic.total_orders }}</p>
      </div>
      <div class="bg-white rounded-lg border border-gray-200 p-4">
        <p class="text-sm text-gray-500">有來源標記比例</p>
        <p class="text-2xl font-bold text-gray-900">{{ trackedPct }}%</p>
        <p class="text-xs text-gray-400">{{ traffic.tracked_orders }} / {{ traffic.total_orders }} 筆</p>
      </div>
    </div>

    <!-- Toggle -->
    <div class="flex gap-2 mb-4">
      <button
        @click="viewMode = 'source'"
        :class="['px-3 py-1.5 text-sm rounded border', viewMode === 'source' ? 'bg-gray-800 text-white border-gray-800' : 'bg-white text-gray-700 border-gray-300']"
      >依來源</button>
      <button
        @click="viewMode = 'channel'"
        :class="['px-3 py-1.5 text-sm rounded border', viewMode === 'channel' ? 'bg-gray-800 text-white border-gray-800' : 'bg-white text-gray-700 border-gray-300']"
      >依管道分類</button>
    </div>

    <!-- Empty state -->
    <div v-if="!traffic.sources.length" class="bg-white rounded-lg border border-gray-200 p-8 text-center text-gray-400">
      尚無訂單來源資料
    </div>

    <!-- Source detail table -->
    <div v-else-if="viewMode === 'source'" class="overflow-x-auto rounded-lg border border-gray-200">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 text-left">
          <tr>
            <th class="px-4 py-3">來源</th>
            <th class="px-4 py-3">中介</th>
            <th class="px-4 py-3">活動</th>
            <th class="px-4 py-3">關鍵字</th>
            <th class="px-4 py-3">內容</th>
            <th class="px-4 py-3 text-right">訂單數</th>
            <th class="px-4 py-3 text-right">金額</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="(row, i) in traffic.sources" :key="i" class="bg-white hover:bg-gray-50">
            <td class="px-4 py-3 font-medium text-gray-900">{{ row.display_source }}</td>
            <td class="px-4 py-3 text-gray-600">{{ row.utm_medium || '—' }}</td>
            <td class="px-4 py-3 text-gray-600">{{ row.utm_campaign || '—' }}</td>
            <td class="px-4 py-3 text-gray-600">{{ row.utm_term || '—' }}</td>
            <td class="px-4 py-3 text-gray-600">{{ row.utm_content || '—' }}</td>
            <td class="px-4 py-3 text-right text-gray-900">{{ row.order_count }}</td>
            <td class="px-4 py-3 text-right text-gray-900">NT${{ row.revenue.toLocaleString() }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Channel group table -->
    <div v-else class="overflow-x-auto rounded-lg border border-gray-200">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 text-left">
          <tr>
            <th class="px-4 py-3">管道</th>
            <th class="px-4 py-3 text-right">訂單數</th>
            <th class="px-4 py-3 text-right">金額</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="(row, i) in groupedSources" :key="i" class="bg-white hover:bg-gray-50">
            <td class="px-4 py-3 font-medium text-gray-900">{{ row.channel }}</td>
            <td class="px-4 py-3 text-right text-gray-900">{{ row.order_count }}</td>
            <td class="px-4 py-3 text-right text-gray-900">NT${{ row.revenue.toLocaleString() }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
