<script setup>
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  rows:  { type: Array, default: () => [] },       // [{ referrer_name, referrer_email, referral_code, order_count, revenue, reward_points }]
  range: { type: [String, Number], default: '30' },
})

const ranges = [
  { value: '7', label: '7 天' },
  { value: '30', label: '30 天' },
  { value: '60', label: '60 天' },
  { value: '90', label: '90 天' },
  { value: 'all', label: '全部' },
]

const setRange = (value) => {
  router.get('/admin/referrals', { range: value }, { preserveState: true, preserveScroll: true })
}

const totals = computed(() => props.rows.reduce((acc, r) => ({
  orders: acc.orders + r.order_count,
  revenue: acc.revenue + r.revenue,
  points: acc.points + r.reward_points,
}), { orders: 0, revenue: 0, points: 0 }))

const fmtMoney = (n) => 'NT$ ' + Number(n || 0).toLocaleString()
</script>

<template>
  <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-5xl mx-auto">
    <h1 class="text-2xl font-bold text-gray-900 mb-1">推薦成效統計</h1>
    <p class="text-sm text-gray-500 mb-6">各推薦人帶來的已付款訂單、營收與回饋積分。</p>

    <!-- Range switcher -->
    <div class="inline-flex rounded-lg bg-gray-100 p-1 mb-6">
      <button
        v-for="r in ranges"
        :key="r.value"
        @click="setRange(r.value)"
        class="px-3 py-1.5 rounded-md text-sm font-medium transition-colors"
        :class="String(range) === r.value ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
      >
        {{ r.label }}
      </button>
    </div>

    <!-- Summary -->
    <div class="grid grid-cols-3 gap-4 mb-6">
      <div class="bg-white shadow-sm rounded-lg p-4">
        <p class="text-xs text-gray-500">推薦訂單數</p>
        <p class="mt-1 text-2xl font-bold text-gray-900">{{ totals.orders.toLocaleString() }}</p>
      </div>
      <div class="bg-white shadow-sm rounded-lg p-4">
        <p class="text-xs text-gray-500">推薦營收</p>
        <p class="mt-1 text-2xl font-bold text-gray-900">{{ fmtMoney(totals.revenue) }}</p>
      </div>
      <div class="bg-white shadow-sm rounded-lg p-4">
        <p class="text-xs text-gray-500">發放回饋積分</p>
        <p class="mt-1 text-2xl font-bold text-brand-teal">{{ totals.points.toLocaleString() }}</p>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white shadow-sm rounded-lg overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left font-medium text-gray-500">推薦人</th>
              <th class="px-4 py-3 text-left font-medium text-gray-500">推薦碼</th>
              <th class="px-4 py-3 text-right font-medium text-gray-500">訂單數</th>
              <th class="px-4 py-3 text-right font-medium text-gray-500">營收</th>
              <th class="px-4 py-3 text-right font-medium text-gray-500">回饋積分</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-if="rows.length === 0">
              <td colspan="5" class="px-4 py-10 text-center text-gray-400">此區間沒有推薦訂單。</td>
            </tr>
            <tr v-for="(r, i) in rows" :key="i" class="hover:bg-gray-50">
              <td class="px-4 py-3">
                <p class="font-medium text-gray-800">{{ r.referrer_name }}</p>
                <p class="text-xs text-gray-400">{{ r.referrer_email }}</p>
              </td>
              <td class="px-4 py-3 font-mono text-gray-600">{{ r.referral_code }}</td>
              <td class="px-4 py-3 text-right text-gray-700">{{ r.order_count.toLocaleString() }}</td>
              <td class="px-4 py-3 text-right text-gray-700">{{ fmtMoney(r.revenue) }}</td>
              <td class="px-4 py-3 text-right font-semibold text-brand-teal">{{ r.reward_points.toLocaleString() }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
