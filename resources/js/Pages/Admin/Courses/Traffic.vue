<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  course: { type: Object, required: true },
  filters: { type: Object, required: true },
  traffic: { type: Object, required: true },
})

// ── 統計篩選 ──────────────────────────────────────────────
const currentDays = computed(() => props.filters.days)

// Free courses count claims, not orders (002 US17). Saying 訂單數 there reads
// as "nobody bought", which is the misunderstanding this story exists to fix.
const countLabel = computed(() => (props.traffic.is_free_claim ? '領取數' : '訂單數'))
const totalLabel = computed(() => `總${countLabel.value}`)

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

// Labels only. The classification itself is the server's (002 FR-046): the
// mirrored copy that used to live here disagreed with it twice (D16, D34), and
// every row now arrives with the channel already resolved.
const CHANNEL_LABELS = {
  paid: '付費廣告',
  social: '社群',
  search: '搜尋引擎',
  email: '電子郵件',
  video: '影音',
  referral: '其他',
  direct: '(直接造訪)',
}

const channelLabel = (channel) => CHANNEL_LABELS[channel] ?? channel

const rate = (row) => (row.conversion_rate === null ? '—' : `${row.conversion_rate}%`)

// Sorting lives in the page, not the server: the row set is one course's worth
// of links, small enough that a round trip per click would only add latency.
const sort = ref({ key: 'order_count', desc: true })

function sortBy(key) {
  if (sort.value.key === key) sort.value.desc = !sort.value.desc
  else sort.value = { key, desc: true }
}

const sortArrow = (key) => (sort.value.key !== key ? '' : sort.value.desc ? ' ↓' : ' ↑')

/** Unmeasured conversion rate sorts last in both directions — it is not a 0%. */
function compare(a, b, key) {
  const av = a[key]
  const bv = b[key]
  if (av === null) return bv === null ? 0 : 1
  if (bv === null) return -1
  return sort.value.desc ? bv - av : av - bv
}

const sortedSources = computed(() =>
  [...props.traffic.sources].sort((a, b) => compare(a, b, sort.value.key))
)

const groupedSources = computed(() => {
  const groups = {}
  for (const row of props.traffic.sources) {
    const ch = row.channel
    if (!groups[ch]) groups[ch] = { channel: ch, views: 0, order_count: 0, revenue: 0 }
    groups[ch].views += row.views
    groups[ch].order_count += row.order_count
    groups[ch].revenue += row.revenue
  }
  return Object.values(groups)
    .map((g) => ({
      ...g,
      conversion_rate: g.views > 0 ? Math.round((g.order_count / g.views) * 1000) / 10 : null,
    }))
    .sort((a, b) => b.order_count - a.order_count || b.views - a.views)
})

const exportUrl = computed(() => {
  const base = `/admin/courses/${props.course.id}/traffic/export`
  return currentDays.value ? `${base}?days=${currentDays.value}` : base
})

// ── UTM 連結生成器 ─────────────────────────────────────────
// `paidMedium` is the value the 付費廣告 checkbox writes. It is the only paid
// signal the server accepts (002 D34/D52) — a bare fbclid is not one — so the
// checkbox exists to spell it correctly, not to be filled in by hand.
const platformPresets = [
  { label: 'Threads', source: 'threads', paidMedium: 'paid_social' },
  { label: 'Instagram', source: 'instagram', paidMedium: 'paid_social' },
  { label: 'Facebook', source: 'facebook', paidMedium: 'paid_social' },
  { label: 'YouTube', source: 'youtube', paidMedium: 'cpc' },
  { label: 'EDM', source: 'email', paidMedium: 'paid_social' },
  { label: 'LINE', source: 'line', paidMedium: 'paid_social' },
]

const utm = ref({ source: '', campaign: '', paid: false })
const copied = ref(false)

function applyPreset(preset) {
  utm.value.source = preset.source
}

// Organic links carry no medium at all: the channel comes from utm_source
// matching the platform registry, so `social` / `video` there only ever made
// the form look like it had been filled in properly.
const paidMedium = computed(() =>
  platformPresets.find((p) => p.source === utm.value.source)?.paidMedium ?? 'paid'
)

const generatedUrl = computed(() => {
  const base = props.course.url
  const params = new URLSearchParams()
  if (utm.value.source.trim())   params.set('utm_source',   utm.value.source.trim())
  if (utm.value.paid)            params.set('utm_medium',   paidMedium.value)
  if (utm.value.campaign.trim()) params.set('utm_campaign', utm.value.campaign.trim())
  const qs = params.toString()
  return qs ? `${base}?${qs}` : base
})

const hasParams = computed(() => utm.value.source || utm.value.campaign || utm.value.paid)

async function copyUrl() {
  await navigator.clipboard.writeText(generatedUrl.value)
  copied.value = true
  setTimeout(() => { copied.value = false }, 2000)
}

function resetUtm() {
  utm.value = { source: '', campaign: '', paid: false }
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
                <Link :href="`/admin/courses/${course.id}/edit`" class="ml-4 text-sm font-medium text-gray-500 hover:text-gray-700">{{ course.name }}</Link>
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
              活動名稱 <span class="text-gray-400 font-normal">utm_campaign</span>
              <span class="text-brand-teal ml-1">← 區分不同貼文用這欄</span>
            </label>
            <input
              v-model="utm.campaign"
              type="text"
              placeholder="0831-限動、母親節優惠、vol-12…"
              class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-teal"
            />
          </div>
        </div>

        <!-- 付費宣告：程式不從 fbclid 猜付費，只認這個 -->
        <label class="flex items-start gap-2 cursor-pointer">
          <input
            v-model="utm.paid"
            type="checkbox"
            class="mt-0.5 rounded border-gray-300 text-brand-teal focus:ring-brand-teal cursor-pointer"
          />
          <span class="text-sm text-gray-700">
            這是付費廣告連結
            <span class="block text-xs text-gray-400">
              勾選後會加上 <code>utm_medium={{ paidMedium }}</code>。不勾的話這條連結會被算成自然流量——付費與自然只靠這個參數分辨。
            </span>
          </span>
        </label>

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
        <p class="text-sm text-gray-500">{{ totalLabel }}</p>
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
      尚無流量與{{ countLabel }}資料
    </div>

    <!-- Source × campaign table -->
    <div v-else-if="viewMode === 'source'" class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-lg overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
          <tr>
            <th class="px-4 py-3">來源</th>
            <th class="px-4 py-3">活動</th>
            <th class="px-4 py-3">管道</th>
            <th
              v-for="col in [
                { key: 'views', label: '瀏覽' },
                { key: 'order_count', label: countLabel },
                { key: 'conversion_rate', label: '轉換率' },
                { key: 'revenue', label: '金額' },
              ]"
              :key="col.key"
              @click="sortBy(col.key)"
              class="px-4 py-3 text-right cursor-pointer select-none hover:text-gray-800"
              :class="sort.key === col.key ? 'text-gray-800' : ''"
            >{{ col.label }}{{ sortArrow(col.key) }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="(row, i) in sortedSources" :key="i" class="bg-white hover:bg-gray-50">
            <td class="px-4 py-3 font-medium text-gray-900">{{ row.display_source }}</td>
            <td class="px-4 py-3 text-gray-600">{{ row.campaign || '—' }}</td>
            <td class="px-4 py-3 text-gray-500">{{ channelLabel(row.channel) }}</td>
            <td class="px-4 py-3 text-right text-gray-900">{{ row.views.toLocaleString() }}</td>
            <td class="px-4 py-3 text-right text-gray-900">{{ row.order_count }}</td>
            <td class="px-4 py-3 text-right text-gray-600">{{ rate(row) }}</td>
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
            <th class="px-4 py-3 text-right">瀏覽</th>
            <th class="px-4 py-3 text-right">{{ countLabel }}</th>
            <th class="px-4 py-3 text-right">轉換率</th>
            <th class="px-4 py-3 text-right">金額</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="(row, i) in groupedSources" :key="i" class="bg-white hover:bg-gray-50">
            <td class="px-4 py-3 font-medium text-gray-900">{{ channelLabel(row.channel) }}</td>
            <td class="px-4 py-3 text-right text-gray-900">{{ row.views.toLocaleString() }}</td>
            <td class="px-4 py-3 text-right text-gray-900">{{ row.order_count }}</td>
            <td class="px-4 py-3 text-right text-gray-600">{{ rate(row) }}</td>
            <td class="px-4 py-3 text-right text-gray-900">NT${{ row.revenue.toLocaleString() }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <p class="text-xs text-gray-400 leading-relaxed">
      瀏覽為「當天不重複的訪客 × 連結」：同一人同一天走兩條不同連結進來算兩次，重整同一條不重複計算。
      轉換率＝{{ countLabel }} ÷ 瀏覽；2026-08-31 之前的{{ countLabel }}沒有對應的瀏覽紀錄，該列轉換率顯示「—」。
      活動欄的「—」代表那條連結沒帶 <code>utm_campaign</code>。
    </p>

  </div>
</template>
