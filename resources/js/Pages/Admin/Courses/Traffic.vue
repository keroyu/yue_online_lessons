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

// ── 統計篩選 ──────────────────────────────────────────────
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

// ── UTM 連結生成器 ─────────────────────────────────────────
const platformPresets = [
  { label: 'Threads', source: 'threads', medium: 'social' },
  { label: 'Instagram', source: 'instagram', medium: 'social' },
  { label: 'Facebook', source: 'facebook', medium: 'social' },
  { label: 'YouTube', source: 'youtube', medium: 'video' },
  { label: 'EDM', source: 'email', medium: 'email' },
  { label: 'LINE', source: 'line', medium: 'social' },
]

const utm = ref({ source: '', medium: '', campaign: '', content: '', term: '' })
const copied = ref(false)

function applyPreset(preset) {
  utm.value.source = preset.source
  utm.value.medium = preset.medium
}

const generatedUrl = computed(() => {
  const base = props.course.url
  const params = new URLSearchParams()
  if (utm.value.source.trim())   params.set('utm_source',   utm.value.source.trim())
  if (utm.value.medium.trim())   params.set('utm_medium',   utm.value.medium.trim())
  if (utm.value.campaign.trim()) params.set('utm_campaign', utm.value.campaign.trim())
  if (utm.value.content.trim())  params.set('utm_content',  utm.value.content.trim())
  if (utm.value.term.trim())     params.set('utm_term',     utm.value.term.trim())
  const qs = params.toString()
  return qs ? `${base}?${qs}` : base
})

const hasParams = computed(() =>
  utm.value.source || utm.value.medium || utm.value.campaign || utm.value.content || utm.value.term
)

async function copyUrl() {
  await navigator.clipboard.writeText(generatedUrl.value)
  copied.value = true
  setTimeout(() => { copied.value = false }, 2000)
}

function resetUtm() {
  utm.value = { source: '', medium: '', campaign: '', content: '', term: '' }
}
</script>

<template>
  <div class="max-w-7xl mx-auto px-4 py-6 space-y-8">

    <!-- Header -->
    <div class="flex items-start justify-between">
      <div>
        <nav class="flex" aria-label="Breadcrumb">
          <ol class="flex items-center space-x-4">
            <li>
              <Link href="/admin/courses" class="text-sm font-medium text-gray-500 hover:text-gray-700">
                課程管理
              </Link>
            </li>
            <li>
              <div class="flex items-center">
                <svg class="flex-shrink-0 h-5 w-5 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                </svg>
                <span class="ml-4 text-sm font-medium text-gray-500">{{ course.name }}</span>
              </div>
            </li>
            <li>
              <div class="flex items-center">
                <svg class="flex-shrink-0 h-5 w-5 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                </svg>
                <span class="ml-4 text-sm font-medium text-gray-900">連結來源追蹤</span>
              </div>
            </li>
          </ol>
        </nav>
        <h1 class="mt-2 text-2xl font-semibold text-gray-900">連結來源追蹤</h1>
      </div>
      <a :href="exportUrl" class="px-3 py-1.5 text-sm bg-brand-teal text-white rounded hover:bg-brand-teal/90">
        匯出 CSV
      </a>
    </div>

    <!-- ── UTM 連結生成器 ── -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-2">
        <svg class="w-4 h-4 text-brand-teal" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
        </svg>
        <h2 class="text-sm font-semibold text-gray-800">追蹤連結生成器</h2>
        <span class="text-xs text-gray-400 ml-1">— 貼到 Threads / IG 貼文或 EDM，追蹤各來源轉換</span>
      </div>

      <div class="p-5 space-y-4">

        <!-- 平台快速選擇 -->
        <div>
          <p class="text-xs font-medium text-gray-500 mb-2">快速套用平台</p>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="p in platformPresets"
              :key="p.label"
              @click="applyPreset(p)"
              :class="[
                'px-3 py-1.5 text-xs rounded-full border transition-colors',
                utm.source === p.source
                  ? 'bg-brand-teal text-white border-brand-teal'
                  : 'bg-gray-50 text-gray-600 border-gray-200 hover:border-brand-teal/40 hover:text-brand-teal',
              ]"
            >
              {{ p.label }}
            </button>
          </div>
        </div>

        <!-- 參數欄位 -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">
              來源 <span class="text-gray-400 font-normal">utm_source</span>
            </label>
            <input
              v-model="utm.source"
              type="text"
              placeholder="threads、instagram、email…"
              class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-teal"
            />
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">
              管道 <span class="text-gray-400 font-normal">utm_medium</span>
            </label>
            <input
              v-model="utm.medium"
              type="text"
              placeholder="social、email、video…"
              class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-teal"
            />
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">
              活動名稱 <span class="text-gray-400 font-normal">utm_campaign</span>
            </label>
            <input
              v-model="utm.campaign"
              type="text"
              placeholder="2026-launch、母親節優惠…"
              class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-teal"
            />
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">
              貼文識別 <span class="text-gray-400 font-normal">utm_content</span>
              <span class="text-brand-teal ml-1">← 區分不同貼文用這欄</span>
            </label>
            <input
              v-model="utm.content"
              type="text"
              placeholder="post-001、bio-link、限動…"
              class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-teal"
            />
          </div>
        </div>

        <!-- 產生結果 -->
        <div class="rounded-lg bg-gray-50 border border-gray-200 p-3">
          <p class="text-xs font-medium text-gray-500 mb-2">產生的追蹤連結</p>
          <p class="text-xs text-gray-700 break-all font-mono leading-relaxed">{{ generatedUrl }}</p>
        </div>

        <!-- 操作按鈕 -->
        <div class="flex gap-2">
          <button
            @click="copyUrl"
            :class="[
              'flex items-center gap-1.5 px-4 py-2 text-sm rounded-lg font-medium transition-colors',
              copied
                ? 'bg-green-600 text-white'
                : 'bg-brand-teal text-white hover:bg-brand-teal/90',
            ]"
          >
            <svg v-if="copied" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
            <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
            </svg>
            {{ copied ? '已複製！' : '複製連結' }}
          </button>
          <button
            v-if="hasParams"
            @click="resetUtm"
            class="px-3 py-2 text-sm text-gray-500 hover:text-gray-700 rounded-lg hover:bg-gray-100"
          >
            清除
          </button>
        </div>

      </div>
    </div>

    <!-- ── 統計區 ── -->
    <!-- Time preset buttons -->
    <div>
      <p class="text-xs font-medium text-gray-500 mb-2">時間範圍</p>
      <div class="flex gap-2">
        <button
          v-for="p in dayPresets"
          :key="p.label"
          @click="setDays(p.value)"
          :class="[
            'px-3 py-1.5 text-sm rounded border',
            currentDays === p.value
              ? 'bg-brand-teal text-white border-brand-teal'
              : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50',
          ]"
        >
          {{ p.label }}
        </button>
      </div>
    </div>

    <!-- Summary cards -->
    <div class="grid grid-cols-2 gap-4">
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
    <div class="flex gap-2">
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
    <div v-else-if="viewMode === 'source'" class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-lg overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
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
    <div v-else class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-lg overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
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
