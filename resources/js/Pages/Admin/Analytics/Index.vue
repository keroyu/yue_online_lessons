<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  funnel: { type: Array, default: () => [] },
  channels: { type: Array, default: () => [] },
  cta: { type: Array, default: () => [] },
  range: { type: String, default: '30' },
  channel: { type: String, default: null },
})

const ranges = [
  { value: '7', label: '7 天' },
  { value: '30', label: '30 天' },
  { value: '90', label: '90 天' },
  { value: 'all', label: '全部' },
]

const channelLabels = {
  paid: '付費廣告',
  social: '社群',
  search: '搜尋引擎',
  // Holds both of our mailing paths (US14), so the channel is named for the
  // medium and the sources below name the letter.
  email: '電子郵件',
  video: '影音',
  referral: '其他來源',
  direct: '直接造訪',
}

// Known platforms get a proper display name; anything else is a referrer host
// and is shown as-is — the host itself is the useful part.
const sourceLabels = {
  instagram: 'Instagram',
  threads: 'Threads',
  facebook: 'Facebook',
  meta: 'Meta（FB/IG 未分）',
  line: 'LINE',
  twitter: 'X / Twitter',
  linkedin: 'LinkedIn',
  google: 'Google',
  bing: 'Bing',
  yahoo: 'Yahoo',
  duckduckgo: 'DuckDuckGo',
  youtube: 'YouTube',
  tiktok: 'TikTok',
  vimeo: 'Vimeo',
  drip: '連鎖信',
  newsletter: '電子報',
  direct: '直接造訪',
  // utm_medium declared paid but nothing identified the platform.
  unknown: '未知平台',
}

const sourceLabel = (source) => {
  if (!source) return '未分類（舊資料）'
  return sourceLabels[source] ?? source
}

const expanded = ref(new Set())

// Single-source channels have nothing to reveal — keep them flat.
const isExpandable = (row) => (row.sources?.length ?? 0) > 1

const toggle = (row) => {
  if (!isExpandable(row)) return
  const next = new Set(expanded.value)
  next.has(row.channel) ? next.delete(row.channel) : next.add(row.channel)
  expanded.value = next
}

const setFilter = (params) => {
  router.get('/admin/analytics', {
    range: params.range ?? props.range,
    channel: params.channel !== undefined ? params.channel : props.channel,
  }, { preserveState: true, preserveScroll: true })
}

const rate = (num, den) => {
  if (!den) return '—'
  return ((num / den) * 100).toFixed(1) + '%'
}

const formatNumber = (n) => (n ?? 0).toLocaleString()
</script>

<template>
  <div class="p-4 sm:p-6 space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <h1 class="text-xl font-bold text-gray-900">行銷分析</h1>

      <div class="flex flex-wrap items-center gap-2">
        <button
          v-for="r in ranges"
          :key="r.value"
          @click="setFilter({ range: r.value })"
          :class="[
            'px-3 py-1.5 text-sm rounded border cursor-pointer',
            range === r.value || (range === 'all' && r.value === 'all')
              ? 'bg-gray-800 text-white border-gray-800'
              : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50',
          ]"
        >{{ r.label }}</button>

        <select
          :value="channel ?? ''"
          @change="setFilter({ channel: $event.target.value || null })"
          class="px-3 py-1.5 text-sm border border-gray-300 rounded bg-white text-gray-700 cursor-pointer hover:bg-gray-50"
        >
          <option value="">全部管道</option>
          <option v-for="(label, key) in channelLabels" :key="key" :value="key">{{ label }}</option>
        </select>
      </div>
    </div>

    <!-- Course funnel -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
      <h2 class="px-4 py-3 text-sm font-semibold text-gray-700 border-b border-gray-100">
        課程轉換漏斗（瀏覽 → 加入購物車 → 結帳 → 成交）
      </h2>

      <div v-if="funnel.length === 0" class="p-8 text-center text-sm text-gray-400">
        此期間尚無流量資料
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm min-w-[860px]">
          <thead>
            <tr class="text-left text-xs text-gray-500 border-b border-gray-100">
              <th class="px-4 py-2.5 font-medium">課程</th>
              <th class="px-4 py-2.5 font-medium text-right">瀏覽</th>
              <th class="px-4 py-2.5 font-medium text-right">加購</th>
              <th class="px-4 py-2.5 font-medium text-right">加購率</th>
              <th class="px-4 py-2.5 font-medium text-right">結帳</th>
              <th class="px-4 py-2.5 font-medium text-right">成交</th>
              <th class="px-4 py-2.5 font-medium text-right">成交率</th>
              <th class="px-4 py-2.5 font-medium text-right">營收</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in funnel" :key="row.course_id" class="border-b border-gray-50 hover:bg-gray-50">
              <td class="px-4 py-3 font-medium text-gray-900">{{ row.course_name }}</td>
              <td class="px-4 py-3 text-right text-gray-900">{{ formatNumber(row.views) }}</td>
              <td class="px-4 py-3 text-right text-gray-900">{{ formatNumber(row.add_to_cart) }}</td>
              <td class="px-4 py-3 text-right text-gray-500">{{ rate(row.add_to_cart, row.views) }}</td>
              <td class="px-4 py-3 text-right text-gray-900">{{ formatNumber(row.checkouts) }}</td>
              <td class="px-4 py-3 text-right text-gray-900">{{ formatNumber(row.purchases) }}</td>
              <td class="px-4 py-3 text-right text-gray-500">{{ rate(row.purchases, row.views) }}</td>
              <td class="px-4 py-3 text-right text-gray-900">NT${{ formatNumber(row.revenue) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Channel breakdown -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
      <h2 class="px-4 py-3 text-sm font-semibold text-gray-700 border-b border-gray-100">
        各管道成效
        <span class="ml-2 font-normal text-xs text-gray-400">點管道可展開來源明細</span>
      </h2>

      <div v-if="channels.length === 0" class="p-8 text-center text-sm text-gray-400">
        此期間尚無管道資料
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm min-w-[720px]">
          <thead>
            <tr class="text-left text-xs text-gray-500 border-b border-gray-100">
              <th class="px-4 py-2.5 font-medium">管道 / 來源</th>
              <th class="px-4 py-2.5 font-medium text-right">瀏覽</th>
              <th class="px-4 py-2.5 font-medium text-right">加購</th>
              <th class="px-4 py-2.5 font-medium text-right">結帳</th>
              <th class="px-4 py-2.5 font-medium text-right">成交</th>
              <th class="px-4 py-2.5 font-medium text-right">成交率</th>
              <th class="px-4 py-2.5 font-medium text-right">營收</th>
            </tr>
          </thead>
          <tbody>
            <template v-for="row in channels" :key="row.channel">
              <tr
                @click="toggle(row)"
                :class="[
                  'border-b border-gray-50 hover:bg-gray-50',
                  isExpandable(row) ? 'cursor-pointer' : '',
                ]"
              >
                <td class="px-4 py-3 font-medium text-gray-900">
                  <span class="inline-block w-4 text-gray-400">
                    <template v-if="isExpandable(row)">{{ expanded.has(row.channel) ? '▾' : '▸' }}</template>
                  </span>
                  {{ channelLabels[row.channel] ?? row.channel }}
                </td>
                <td class="px-4 py-3 text-right text-gray-900">{{ formatNumber(row.views) }}</td>
                <td class="px-4 py-3 text-right text-gray-900">{{ formatNumber(row.add_to_cart) }}</td>
                <td class="px-4 py-3 text-right text-gray-900">{{ formatNumber(row.checkouts) }}</td>
                <td class="px-4 py-3 text-right text-gray-900">{{ formatNumber(row.purchases) }}</td>
                <td class="px-4 py-3 text-right text-gray-500">{{ rate(row.purchases, row.views) }}</td>
                <td class="px-4 py-3 text-right text-gray-900">NT${{ formatNumber(row.revenue) }}</td>
              </tr>

              <tr
                v-for="sub in (expanded.has(row.channel) ? row.sources : [])"
                :key="`${row.channel}-${sub.source}`"
                class="border-b border-gray-50 bg-gray-50/60 text-gray-600"
              >
                <td class="pl-12 pr-4 py-2">{{ sourceLabel(sub.source) }}</td>
                <td class="px-4 py-2 text-right">{{ formatNumber(sub.views) }}</td>
                <td class="px-4 py-2 text-right">{{ formatNumber(sub.add_to_cart) }}</td>
                <td class="px-4 py-2 text-right">{{ formatNumber(sub.checkouts) }}</td>
                <td class="px-4 py-2 text-right">{{ formatNumber(sub.purchases) }}</td>
                <td class="px-4 py-2 text-right text-gray-500">{{ rate(sub.purchases, sub.views) }}</td>
                <td class="px-4 py-2 text-right">NT${{ formatNumber(sub.revenue) }}</td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Blog CTA clicks -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
      <h2 class="px-4 py-3 text-sm font-semibold text-gray-700 border-b border-gray-100">文章引流成效（CTA 點擊）</h2>

      <div v-if="cta.length === 0" class="p-8 text-center text-sm text-gray-400">
        此期間尚無文章引流點擊
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm min-w-[560px]">
          <thead>
            <tr class="text-left text-xs text-gray-500 border-b border-gray-100">
              <th class="px-4 py-2.5 font-medium">文章</th>
              <th class="px-4 py-2.5 font-medium">引流課程</th>
              <th class="px-4 py-2.5 font-medium text-right">點擊數</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in cta" :key="`${row.post_id}-${row.course_id}`" class="border-b border-gray-50 hover:bg-gray-50">
              <td class="px-4 py-3 text-gray-900">{{ row.post_title }}</td>
              <td class="px-4 py-3 text-gray-600">{{ row.course_name }}</td>
              <td class="px-4 py-3 text-right font-medium text-gray-900">{{ formatNumber(row.clicks) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <p class="text-xs text-gray-400 leading-relaxed">
      計數為趨勢參考（爬蟲過濾、同工作階段去重）；金額對帳請以「交易紀錄」為準。管道歸類依最後觸點來源。<br>
      管道歸類自 2026-08 起納入 referrer 網域（沒帶 UTM 的 IG／Threads 流量會歸到「社群」），先前資料未重算，跨這個時間點比較管道數字會有落差；「未分類（舊資料）」即該次改版前寫入的列。<br>
      2026-08-04 起 <code class="text-gray-500">fbclid</code> 不再視為付費訊號（Meta 對每一次從 FB／IG 點出的外連結都會附加，含自然貼文與簡介連結），歷史的「付費廣告 › Facebook」已一併併入「社群 › Meta（FB/IG 未分）」；跨這個時間點比較「付費廣告」的數字同樣會有落差。<br>
      付費流量請在廣告網址參數填 <code class="text-gray-500">utm_medium=paid_social</code> 才算得準——付費只認 <code class="text-gray-500">utm_medium</code> 與 Google／TikTok 的廣告 click id，程式不從 <code class="text-gray-500">fbclid</code> 猜。<br>
      IG／FB 的 App 內建瀏覽器常不送 referrer，那部分流量會落在「直接造訪」——要精準追蹤，貼文連結請帶 <code class="text-gray-500">?utm_source=instagram</code>。<br>
      2026-08-05 起，連鎖信與電子報寄出時會自動替信中的本站連結加上來源參數，「電子郵件」管道因此可展開為「連鎖信 ／ 電子報」；此日之前寄出的信沒有參數，那些點擊仍記在「直接造訪」。
    </p>
  </div>
</template>
