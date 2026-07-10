<script setup>
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const tabs = [
  { label: '一般折扣碼', href: '/admin/coupons' },
  { label: '輪換折扣碼', href: '/admin/coupon-chains' },
]

defineOptions({ layout: AdminLayout })

defineProps({
  // Laravel paginator：{ data, current_page, last_page, ... }
  coupons: { type: Object, required: true },
})

const goToPage = (page) => {
  router.get('/admin/coupons', { page }, { preserveState: true, preserveScroll: true })
}

const toggle = (coupon) => {
  router.patch(`/admin/coupons/${coupon.id}/toggle`, {}, { preserveScroll: true })
}

const destroy = (coupon) => {
  if (!confirm(`確定刪除折扣碼「${coupon.code}」？刪除後相同代碼不可重建，歷史訂單不受影響。`)) return
  router.delete(`/admin/coupons/${coupon.id}`, { preserveScroll: true })
}
</script>

<template>
  <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-900">折扣碼管理</h1>
      <p class="mt-1 text-sm text-gray-500">建立與管理優惠折扣碼，查看使用統計</p>
    </div>

    <!-- Tabs -->
    <div class="flex items-center justify-between mb-6 border-b border-gray-200">
      <nav class="flex gap-6">
        <Link
          v-for="tab in tabs"
          :key="tab.href"
          :href="tab.href"
          class="pb-3 text-sm font-medium border-b-2 -mb-px transition-colors"
          :class="tab.href === '/admin/coupons'
            ? 'border-brand-teal text-brand-teal'
            : 'border-transparent text-gray-500 hover:text-gray-700'"
        >
          {{ tab.label }}
        </Link>
      </nav>
      <Link
        href="/admin/coupons/create"
        class="mb-3 inline-flex items-center px-4 py-2 rounded-lg bg-brand-teal text-white text-sm font-semibold hover:bg-brand-teal/90"
      >
        新增折扣碼
      </Link>
    </div>

    <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-lg overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-100">
        <thead class="bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">代碼</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">折扣</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">適用範圍</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">到期</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">名額剩餘</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">已使用</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">狀態</th>
            <th class="relative px-4 py-3"><span class="sr-only">操作</span></th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-100">
          <tr v-for="c in coupons.data" :key="c.id">
            <td class="px-4 py-4 text-sm font-mono font-semibold text-gray-900">{{ c.code }}</td>
            <td class="px-4 py-4 text-sm text-gray-700">{{ c.type_label }}</td>
            <td class="px-4 py-4 text-sm text-gray-500">{{ c.scope_label }}</td>
            <td class="px-4 py-4 text-sm text-gray-500">{{ c.expires_label }}</td>
            <td class="px-4 py-4 text-sm text-gray-500">{{ c.remaining_label }}</td>
            <td class="px-4 py-4 text-sm text-gray-500">{{ c.used_count }}</td>
            <td class="px-4 py-4 text-sm">
              <button
                @click="toggle(c)"
                :class="c.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-500'"
                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
              >
                {{ c.is_active ? '啟用中' : '已停用' }}
              </button>
            </td>
            <td class="px-4 py-4 text-right text-sm font-medium whitespace-nowrap">
              <Link :href="`/admin/coupons/${c.id}`" class="text-brand-teal hover:underline">統計</Link>
              <Link :href="`/admin/coupons/${c.id}/edit`" class="ml-3 text-brand-teal hover:text-brand-navy">編輯</Link>
              <button @click="destroy(c)" class="ml-3 text-red-500 hover:text-red-700">刪除</button>
            </td>
          </tr>
          <tr v-if="coupons.data.length === 0">
            <td colspan="8" class="px-4 py-10 text-center text-sm text-gray-500">尚無折扣碼，點擊右上角「新增折扣碼」開始建立</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="coupons.last_page > 1" class="mt-4 flex items-center justify-center">
      <nav class="flex items-center space-x-2">
        <button
          @click="goToPage(coupons.current_page - 1)"
          :disabled="coupons.current_page === 1"
          class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          上一頁
        </button>
        <span class="text-sm text-gray-700">
          {{ coupons.current_page }} / {{ coupons.last_page }}
        </span>
        <button
          @click="goToPage(coupons.current_page + 1)"
          :disabled="coupons.current_page === coupons.last_page"
          class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          下一頁
        </button>
      </nav>
    </div>
  </div>
</template>
