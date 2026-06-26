<script setup>
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

defineOptions({ layout: AdminLayout })

defineProps({
  chains: { type: Object, required: true },
})

const goToPage = (page) => {
  router.get('/admin/coupon-chains', { page }, { preserveState: true, preserveScroll: true })
}

const toggle = (chain) => {
  router.patch(`/admin/coupon-chains/${chain.id}/toggle`, {}, { preserveScroll: true })
}

const destroy = (chain) => {
  if (!confirm(`確定刪除輪換折扣碼「${chain.alias}」？刪除後歷史代碼保留，佔位符 ${chain.placeholder} 將停止替換。`)) return
  router.delete(`/admin/coupon-chains/${chain.id}`, { preserveScroll: true })
}
</script>

<template>
  <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-5xl mx-auto">
    <div class="sm:flex sm:items-center sm:justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">輪換折扣碼</h1>
        <p class="mt-1 text-sm text-gray-500">設定別名 → 在促銷內容插入 {alias} → 兌換後自動補新碼</p>
      </div>
      <Link
        href="/admin/coupon-chains/create"
        class="mt-3 sm:mt-0 inline-flex items-center px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700"
      >
        新增輪換折扣碼
      </Link>
    </div>

    <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">佔位符</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">折扣</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">適用範圍</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">每碼名額</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">當前代碼</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">狀態</th>
            <th class="relative px-4 py-3"><span class="sr-only">操作</span></th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="c in chains.data" :key="c.id">
            <td class="px-4 py-4 text-sm">
              <span class="font-mono font-semibold text-indigo-700">{{ c.placeholder }}</span>
            </td>
            <td class="px-4 py-4 text-sm text-gray-700">{{ c.type_label }}</td>
            <td class="px-4 py-4 text-sm text-gray-500">{{ c.scope_label }}</td>
            <td class="px-4 py-4 text-sm text-gray-500">{{ c.code_max_uses === 0 ? '無限制' : c.code_max_uses }}</td>
            <td class="px-4 py-4 text-sm">
              <span v-if="c.current_code" class="font-mono bg-amber-50 text-amber-800 px-2 py-0.5 rounded">{{ c.current_code }}</span>
              <span v-else class="text-gray-400">—</span>
            </td>
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
              <Link :href="`/admin/coupon-chains/${c.id}`" class="text-brand-teal hover:underline">歷史</Link>
              <Link :href="`/admin/coupon-chains/${c.id}/edit`" class="ml-3 text-indigo-600 hover:text-indigo-900">編輯</Link>
              <button @click="destroy(c)" class="ml-3 text-red-500 hover:text-red-700">刪除</button>
            </td>
          </tr>
          <tr v-if="chains.data.length === 0">
            <td colspan="7" class="px-4 py-10 text-center text-sm text-gray-500">尚無輪換折扣碼</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="chains.last_page > 1" class="mt-4 flex items-center justify-center">
      <nav class="flex items-center space-x-2">
        <button
          @click="goToPage(chains.current_page - 1)"
          :disabled="chains.current_page === 1"
          class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
        >上一頁</button>
        <span class="text-sm text-gray-700">{{ chains.current_page }} / {{ chains.last_page }}</span>
        <button
          @click="goToPage(chains.current_page + 1)"
          :disabled="chains.current_page === chains.last_page"
          class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
        >下一頁</button>
      </nav>
    </div>
  </div>
</template>
