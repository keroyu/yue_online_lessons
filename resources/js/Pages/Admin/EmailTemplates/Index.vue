<script setup>
import { Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

defineProps({
  templates: {
    type: Array,
    required: true,
  },
})

const eventTypeLabels = {
  high_ticket_booking_confirmation: '客製服務預約確認',
  course_gifted: '課程贈禮通知',
  lesson_added: '課程新增小節通知',
}
</script>

<template>
  <AdminLayout>
    <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-4xl mx-auto">
      <div class="sm:flex sm:items-center sm:justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Email 模板管理</h1>
          <p class="mt-1 text-sm text-gray-500">管理系統自動寄送的 Email 模板內容</p>
        </div>
      </div>

      <div class="bg-white shadow-sm ring-1 ring-gray-900/5 sm:rounded-xl overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">模板名稱</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">事件類型</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">主旨</th>
              <th class="relative px-6 py-3"><span class="sr-only">操作</span></th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="template in templates" :key="template.id">
              <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ template.name }}</td>
              <td class="px-6 py-4 text-sm text-gray-500">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                  {{ eventTypeLabels[template.event_type] || template.event_type }}
                </span>
              </td>
              <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">{{ template.subject }}</td>
              <td class="px-6 py-4 text-right text-sm font-medium">
                <Link
                  :href="`/admin/email-templates/${template.id}/edit`"
                  class="text-indigo-600 hover:text-indigo-900"
                >
                  編輯
                </Link>
              </td>
            </tr>
            <tr v-if="templates.length === 0">
              <td colspan="4" class="px-6 py-10 text-center text-sm text-gray-500">
                尚無模板，請先執行 EmailTemplateSeeder
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AdminLayout>
</template>
