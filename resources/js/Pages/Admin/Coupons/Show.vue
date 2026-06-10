<script setup>
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  coupon:  { type: Object, required: true },
  range:   { type: [String, Number], default: '30' },
  summary: { type: Object, required: true },   // { count, revenue, discount_total }
  details: { type: Array, default: () => [] },  // [{ email, paid_at, total, original }]
})

const ranges = [
  { value: '7', label: '7 天' },
  { value: '30', label: '30 天' },
  { value: '60', label: '60 天' },
  { value: '90', label: '90 天' },
  { value: 'all', label: '全部' },
]

const setRange = (value) => {
  router.get(`/admin/coupons/${props.coupon.id}`, { range: value }, {
    preserveState: true,
    preserveScroll: true,
  })
}

const fmtMoney = (n) => 'NT$ ' + Number(n || 0).toLocaleString()
const fmtTime = (iso) => {
  if (!iso) return '—'
  const d = new Date(iso)
  return d.toLocaleString('zh-TW', { hour12: false })
}
</script>

<template>
  <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-4xl mx-auto">
    <div class="mb-6">
      <Link href="/admin/coupons" class="text-sm text-gray-500 hover:text-gray-700">&larr; 返回折扣碼列表</Link>
      <h1 class="text-2xl font-bold text-gray-900 mt-2">
        折扣碼統計：<span class="font-mono">{{ coupon.code }}</span>
      </h1>
      <p class="mt-1 text-sm text-gray-500">{{ coupon.type_label }} · {{ coupon.scope_label }}</p>
    </div>

    <!-- Range switcher -->
    <div class="inline-flex rounded-lg bg-gray-100 p-1 mb-6">
      <button
        v-for="r in ranges"
        :key="r.value"
        @click="setRange(r.value)"
        :class="String(range) === r.value
          ? 'bg-white text-gray-900 shadow-sm'
          : 'text-gray-500 hover:text-gray-700'"
        class="px-4 py-1.5 rounded-md text-sm font-medium transition-colors"
      >
        {{ r.label }}
      </button>
    </div>

    <!-- Summary cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
      <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-xl p-5">
        <p class="text-sm text-gray-500">完成交易筆數</p>
        <p class="text-2xl font-bold text-gray-900 mt-1">{{ summary.count }}</p>
      </div>
      <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-xl p-5">
        <p class="text-sm text-gray-500">總營收（折後實付）</p>
        <p class="text-2xl font-bold text-brand-teal mt-1">{{ fmtMoney(summary.revenue) }}</p>
      </div>
      <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-xl p-5">
        <p class="text-sm text-gray-500">總折抵金額</p>
        <p class="text-2xl font-bold text-amber-600 mt-1">{{ fmtMoney(summary.discount_total) }}</p>
      </div>
    </div>

    <!-- Detail table -->
    <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">會員 Email</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">付款確認時間</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">原始金額</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">結帳金額（折後）</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="(d, i) in details" :key="i">
            <td class="px-4 py-3 text-sm text-gray-700">{{ d.email }}</td>
            <td class="px-4 py-3 text-sm text-gray-500">{{ fmtTime(d.paid_at) }}</td>
            <td class="px-4 py-3 text-sm text-gray-500 text-right">{{ d.original !== null ? fmtMoney(d.original) : '—' }}</td>
            <td class="px-4 py-3 text-sm font-medium text-gray-900 text-right">{{ fmtMoney(d.total) }}</td>
          </tr>
          <tr v-if="details.length === 0">
            <td colspan="4" class="px-4 py-10 text-center text-sm text-gray-500">此期間無交易記錄</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
