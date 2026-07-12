<script setup>
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
  email: '電子報',
  video: '影音',
  referral: '其他來源',
  direct: '直接造訪',
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
      <h2 class="px-4 py-3 text-sm font-semibold text-gray-700 border-b border-gray-100">各管道成效</h2>

      <div v-if="channels.length === 0" class="p-8 text-center text-sm text-gray-400">
        此期間尚無管道資料
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm min-w-[720px]">
          <thead>
            <tr class="text-left text-xs text-gray-500 border-b border-gray-100">
              <th class="px-4 py-2.5 font-medium">管道</th>
              <th class="px-4 py-2.5 font-medium text-right">瀏覽</th>
              <th class="px-4 py-2.5 font-medium text-right">加購</th>
              <th class="px-4 py-2.5 font-medium text-right">結帳</th>
              <th class="px-4 py-2.5 font-medium text-right">成交</th>
              <th class="px-4 py-2.5 font-medium text-right">成交率</th>
              <th class="px-4 py-2.5 font-medium text-right">營收</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in channels" :key="row.channel" class="border-b border-gray-50 hover:bg-gray-50">
              <td class="px-4 py-3 font-medium text-gray-900">{{ channelLabels[row.channel] ?? row.channel }}</td>
              <td class="px-4 py-3 text-right text-gray-900">{{ formatNumber(row.views) }}</td>
              <td class="px-4 py-3 text-right text-gray-900">{{ formatNumber(row.add_to_cart) }}</td>
              <td class="px-4 py-3 text-right text-gray-900">{{ formatNumber(row.checkouts) }}</td>
              <td class="px-4 py-3 text-right text-gray-900">{{ formatNumber(row.purchases) }}</td>
              <td class="px-4 py-3 text-right text-gray-500">{{ rate(row.purchases, row.views) }}</td>
              <td class="px-4 py-3 text-right text-gray-900">NT${{ formatNumber(row.revenue) }}</td>
            </tr>
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

    <p class="text-xs text-gray-400">
      計數為趨勢參考（爬蟲過濾、同工作階段去重）；金額對帳請以「交易紀錄」為準。管道歸類依最後觸點來源。
    </p>
  </div>
</template>
