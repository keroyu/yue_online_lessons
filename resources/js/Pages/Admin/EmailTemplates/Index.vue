<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  templates: {
    type: Array,
    required: true,
  },
  notifyCc: {
    type: String,
    default: '',
  },
  notifyCcDefault: {
    type: String,
    default: '',
  },
  supportEmail: {
    type: String,
    default: '',
  },
  supportEmailDefault: {
    type: String,
    default: '',
  },
})

const notifyCcForm = useForm({ notify_cc: props.notifyCc })

const saveNotifyCc = () => {
  notifyCcForm.put('/admin/email-templates/notify-cc', { preserveScroll: true })
}

const supportEmailForm = useForm({ support_email: props.supportEmail })

const saveSupportEmail = () => {
  supportEmailForm.put('/admin/email-templates/support-email', { preserveScroll: true })
}

const eventTypeLabels = {
  high_ticket_booking_confirmation: '客製服務預約確認',
  course_gifted: '課程贈禮通知',
  lesson_added: '課程新增小節通知',
  high_ticket_slot_available: '客製服務新時段通知',
  lead_converted: '顧問成交開通通知',
  high_ticket_booking_rescheduled: '客製服務預約已改期',
  high_ticket_booking_cancelled: '客製服務預約已取消',
  high_ticket_consultation_reminder: '客製服務面談提醒',
}
</script>

<template>
  <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
      <div class="sm:flex sm:items-center sm:justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Email 模板管理</h1>
          <p class="mt-1 text-sm text-gray-500">管理系統自動寄送的 Email 模板內容</p>
        </div>
      </div>

      <!-- Two related-but-distinct routing settings. Side by side from lg up:
           full width each was a 1200px row holding one short email address. -->
      <div class="grid gap-6 lg:grid-cols-2 mb-6">
        <div class="flex flex-col bg-white shadow-sm ring-1 ring-gray-900/5 rounded-lg p-4 sm:p-5">
          <h2 class="text-sm font-semibold text-gray-900">預約通知收件者（CC）</h2>
          <p class="mt-1 text-sm text-gray-500">
            預約確認信原則上只副本給<strong>該時段的負責顧問</strong>；這份清單只在
            <strong>該筆預約沒有指派顧問</strong>時作為後備，避免沒有任何人收到通知。
            多筆用逗號分隔，留空則使用預設值
            <span class="font-mono text-gray-600">{{ notifyCcDefault }}</span>。
          </p>
          <div class="mt-auto flex flex-col sm:flex-row gap-3 pt-3">
            <input
              v-model="notifyCcForm.notify_cc"
              type="text"
              :placeholder="notifyCcDefault"
              class="block w-full rounded-lg border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-brand-teal focus:ring-brand-teal"
              :class="{ 'border-red-300': notifyCcForm.errors.notify_cc }"
              @keyup.enter="saveNotifyCc"
            />
            <button
              type="button"
              :disabled="notifyCcForm.processing"
              class="shrink-0 px-4 py-2 rounded-lg text-sm font-medium text-white bg-brand-teal hover:bg-brand-navy transition-colors cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
              @click="saveNotifyCc"
            >
              {{ notifyCcForm.processing ? '儲存中...' : '儲存' }}
            </button>
          </div>
          <p v-if="notifyCcForm.errors.notify_cc" class="mt-2 text-sm text-red-600">{{ notifyCcForm.errors.notify_cc }}</p>
        </div>

        <div class="flex flex-col bg-white shadow-sm ring-1 ring-gray-900/5 rounded-lg p-4 sm:p-5">
          <h2 class="text-sm font-semibold text-gray-900">客服信箱</h2>
          <p class="mt-1 text-sm text-gray-500">
            系統信件與模板中請訪客聯絡的對外信箱。可在下方任何模板插入
            <code class="font-mono text-gray-600">&#123;&#123;support_email&#125;&#125;</code>
            變數，改這裡就會一起更新。留空則使用預設值
            <span class="font-mono text-gray-600">{{ supportEmailDefault }}</span>。
            <br>
            <span class="text-gray-400">這與「預約通知收件者」是不同角色：那是誰接手這條 lead，這是訪客有問題時寫給誰。</span>
          </p>
          <div class="mt-auto flex flex-col sm:flex-row gap-3 pt-3">
            <input
              v-model="supportEmailForm.support_email"
              type="email"
              :placeholder="supportEmailDefault"
              class="block w-full rounded-lg border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-brand-teal focus:ring-brand-teal"
              :class="{ 'border-red-300': supportEmailForm.errors.support_email }"
              @keyup.enter="saveSupportEmail"
            />
            <button
              type="button"
              :disabled="supportEmailForm.processing"
              class="shrink-0 px-4 py-2 rounded-lg text-sm font-medium text-white bg-brand-teal hover:bg-brand-navy transition-colors cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
              @click="saveSupportEmail"
            >
              {{ supportEmailForm.processing ? '儲存中...' : '儲存' }}
            </button>
          </div>
          <p v-if="supportEmailForm.errors.support_email" class="mt-2 text-sm text-red-600">{{ supportEmailForm.errors.support_email }}</p>
        </div>
      </div>

      <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-lg overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100">
          <thead class="bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
            <tr>
              <th class="px-4 py-3 text-left">模板名稱</th>
              <th class="px-4 py-3 text-left">事件類型</th>
              <th class="px-4 py-3 text-left">主旨</th>
              <th class="relative px-4 py-3"><span class="sr-only">操作</span></th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-100">
            <tr v-for="template in templates" :key="template.id">
              <td class="px-4 py-4 text-sm font-medium text-gray-900">{{ template.name }}</td>
              <td class="px-4 py-4 text-sm text-gray-500">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                  {{ eventTypeLabels[template.event_type] || template.event_type }}
                </span>
              </td>
              <td class="px-4 py-4 text-sm text-gray-500 max-w-xs truncate">{{ template.subject }}</td>
              <td class="px-4 py-4 text-right text-sm font-medium">
                <Link
                  :href="`/admin/email-templates/${template.id}/edit`"
                  class="text-brand-teal hover:text-brand-navy"
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
</template>
