<script setup>
import { Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

defineOptions({ layout: AdminLayout })

defineProps({
  broadcast: { type: Object, required: true },
  openedRecipients: { type: Object, required: true },
})
</script>

<template>
  <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-3xl mx-auto">
    <Link href="/admin/broadcasts" class="text-sm text-gray-400 hover:text-brand-teal">← 回電子報列表</Link>
    <h1 class="text-2xl font-bold text-gray-900 mt-2">{{ broadcast.subject }}</h1>
    <p v-if="broadcast.post_url" class="mt-1">
      <a :href="broadcast.post_url" target="_blank" class="text-sm text-brand-teal hover:underline">前台文章 ↗</a>
    </p>

    <div class="grid grid-cols-3 gap-4 mt-6">
      <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-lg p-4 text-center">
        <div class="text-2xl font-bold text-gray-900">{{ broadcast.recipients_count }}</div>
        <div class="text-xs text-gray-500 mt-1">收件人數</div>
      </div>
      <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-lg p-4 text-center">
        <div class="text-2xl font-bold text-gray-900">{{ broadcast.opened_count }}</div>
        <div class="text-xs text-gray-500 mt-1">開信人數</div>
      </div>
      <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-lg p-4 text-center">
        <div class="text-2xl font-bold text-brand-teal">{{ broadcast.open_rate === null ? '—' : broadcast.open_rate + '%' }}</div>
        <div class="text-xs text-gray-500 mt-1">開信率</div>
      </div>
    </div>

    <p class="text-xs text-gray-400 mt-3">
      注意：Apple Mail 隱私保護會預抓追蹤像素，可能使開信率略為高估，僅供參考。
    </p>

    <h2 class="text-lg font-semibold text-gray-900 mt-8 mb-3">已開信名單</h2>
    <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-lg overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
          <tr>
            <th class="px-4 py-3 font-medium">Email</th>
            <th class="px-4 py-3 font-medium">開信時間</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="r in openedRecipients.data" :key="r.email" class="hover:bg-gray-50">
            <td class="px-4 py-3 text-gray-900">{{ r.email }}</td>
            <td class="px-4 py-3 text-gray-500">{{ r.opened_at }}</td>
          </tr>
          <tr v-if="!openedRecipients.data.length">
            <td colspan="2" class="px-4 py-8 text-center text-gray-400">尚無開信紀錄。</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
