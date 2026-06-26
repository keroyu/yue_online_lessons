<script setup>
import { Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

defineOptions({ layout: AdminLayout })

defineProps({
  chain: { type: Object, required: true },
  codes: { type: Array, default: () => [] },
})

const fmtTime = (iso) => {
  if (!iso) return '—'
  return new Date(iso).toLocaleString('zh-TW', { hour12: false })
}
</script>

<template>
  <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-4xl mx-auto">
    <div class="mb-6">
      <Link href="/admin/coupon-chains" class="text-sm text-gray-500 hover:text-gray-700">&larr; 返回輪換折扣碼列表</Link>
      <h1 class="text-2xl font-bold text-gray-900 mt-2">
        輪換折扣碼：<span class="font-mono text-indigo-700">{{ chain.placeholder }}</span>
      </h1>
      <p class="mt-1 text-sm text-gray-500">{{ chain.type_label }} · {{ chain.scope_label }} · 每碼名額：{{ chain.code_max_uses === 0 ? '無限制' : chain.code_max_uses }}</p>
    </div>

    <!-- 當前代碼 highlight -->
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-5 mb-6 flex items-center justify-between">
      <div>
        <p class="text-sm text-amber-700 font-medium">當前有效代碼</p>
        <p class="text-3xl font-mono font-bold text-amber-900 mt-1">{{ chain.current_code ?? '無（已停用或無可用碼）' }}</p>
      </div>
      <Link :href="`/admin/coupon-chains/${chain.id}/edit`" class="text-sm text-indigo-600 hover:underline">編輯設定</Link>
    </div>

    <!-- 歷史代碼表 -->
    <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl overflow-x-auto">
      <div class="px-4 py-3 border-b border-gray-200">
        <h2 class="text-sm font-semibold text-gray-700">歷史生成代碼（共 {{ codes.length }} 支）</h2>
      </div>
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">代碼</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">已使用</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">名額</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">生成時間</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">狀態</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="c in codes" :key="c.id" :class="c.is_current ? 'bg-amber-50' : ''">
            <td class="px-4 py-3 text-sm font-mono font-semibold" :class="c.is_current ? 'text-amber-900' : 'text-gray-700'">
              {{ c.code }}
              <span v-if="c.is_current" class="ml-2 text-xs text-amber-600 font-normal">當前</span>
            </td>
            <td class="px-4 py-3 text-sm text-gray-600">{{ c.used_count }}</td>
            <td class="px-4 py-3 text-sm text-gray-500">{{ c.max_uses ?? '無限制' }}</td>
            <td class="px-4 py-3 text-sm text-gray-500">{{ fmtTime(c.created_at) }}</td>
            <td class="px-4 py-3 text-sm">
              <span
                :class="c.is_current
                  ? 'bg-green-100 text-green-800'
                  : (c.used_count > 0 ? 'bg-gray-100 text-gray-500' : 'bg-yellow-100 text-yellow-700')"
                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
              >
                {{ c.is_current ? '有效' : (c.used_count > 0 ? '已兌換' : '停用') }}
              </span>
            </td>
          </tr>
          <tr v-if="codes.length === 0">
            <td colspan="5" class="px-4 py-10 text-center text-sm text-gray-500">尚無代碼記錄</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
