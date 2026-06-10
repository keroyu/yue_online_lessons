<script setup>
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

defineOptions({ layout: AdminLayout })

defineProps({
  coupons: { type: Array, default: () => [] },
})

const toggle = (coupon) => {
  router.patch(`/admin/coupons/${coupon.id}/toggle`, {}, { preserveScroll: true })
}

const destroy = (coupon) => {
  if (!confirm(`確定刪除折扣碼「${coupon.code}」？刪除後相同代碼不可重建，歷史訂單不受影響。`)) return
  router.delete(`/admin/coupons/${coupon.id}`, { preserveScroll: true })
}
</script>

<template>
  <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-5xl mx-auto">
    <div class="sm:flex sm:items-center sm:justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">折扣碼管理</h1>
        <p class="mt-1 text-sm text-gray-500">建立與管理優惠折扣碼，查看使用統計</p>
      </div>
      <Link
        href="/admin/coupons/create"
        class="mt-3 sm:mt-0 inline-flex items-center px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700"
      >
        新增折扣碼
      </Link>
    </div>

    <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
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
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="c in coupons" :key="c.id">
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
              <Link :href="`/admin/coupons/${c.id}/edit`" class="ml-3 text-indigo-600 hover:text-indigo-900">編輯</Link>
              <button @click="destroy(c)" class="ml-3 text-red-500 hover:text-red-700">刪除</button>
            </td>
          </tr>
          <tr v-if="coupons.length === 0">
            <td colspan="8" class="px-4 py-10 text-center text-sm text-gray-500">尚無折扣碼，點擊右上角「新增折扣碼」開始建立</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
