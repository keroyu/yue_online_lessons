<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

const axios = window.axios

const emit = defineEmits(['close'])

const emailsText = ref('')
const importing = ref(false)
const result = ref(null)
const error = ref(null)

const handleImport = async () => {
  if (!emailsText.value.trim()) return

  importing.value = true
  error.value = null
  result.value = null

  try {
    const response = await axios.post('/admin/members/import', {
      emails: emailsText.value,
    })

    result.value = response.data
  } catch (err) {
    if (err.response?.data?.errors?.emails) {
      error.value = err.response.data.errors.emails[0]
    } else {
      error.value = err.response?.data?.message || '匯入失敗，請稍後再試'
    }
  } finally {
    importing.value = false
  }
}

const handleClose = () => {
  if (result.value?.created_count > 0) {
    router.reload({ only: ['members', 'matchingCount'] })
  }
  emit('close')
}
</script>

<template>
  <div class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex min-h-full items-center justify-center p-4">
      <!-- Backdrop -->
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="handleClose" />

      <!-- Modal panel -->
      <div class="relative w-full max-w-lg bg-white rounded-lg shadow-xl z-10">
        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
          <h2 class="text-lg font-semibold text-gray-900">匯入會員 Email 名單</h2>
          <button
            type="button"
            @click="handleClose"
            class="text-gray-400 hover:text-gray-600 cursor-pointer"
          >
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Body -->
        <div class="px-6 py-4">
          <!-- Result summary -->
          <div v-if="result" class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg">
            <div class="flex items-start gap-2">
              <svg class="h-5 w-5 text-green-600 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
              <div class="text-sm text-green-800">
                <p class="font-medium">匯入完成</p>
                <p>{{ result.message }}</p>
              </div>
            </div>
          </div>

          <!-- Invalid emails list -->
          <div v-if="result?.invalid_emails?.length > 0" class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
            <p class="text-sm font-medium text-yellow-800 mb-1">無效格式 Email（{{ result.invalid_emails.length }} 個）</p>
            <ul class="text-xs text-yellow-700 font-mono space-y-0.5 max-h-32 overflow-y-auto">
              <li v-for="email in result.invalid_emails" :key="email">{{ email }}</li>
            </ul>
          </div>

          <!-- Error -->
          <div v-if="error" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-sm text-red-800">{{ error }}</p>
          </div>

          <!-- Input -->
          <div v-if="!result">
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Email 名單
            </label>
            <p class="text-xs text-gray-500 mb-2">
              每行一個，或以半形逗號分隔。已存在的 Email 將自動略過。
            </p>
            <textarea
              v-model="emailsText"
              rows="8"
              placeholder="example1@gmail.com&#10;example2@gmail.com&#10;或 a@example.com, b@example.com"
              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm font-mono text-xs"
              :disabled="importing"
            />
          </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
          <button
            type="button"
            @click="handleClose"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 cursor-pointer"
          >
            {{ result ? '關閉' : '取消' }}
          </button>
          <button
            v-if="!result"
            type="button"
            @click="handleImport"
            :disabled="importing || !emailsText.trim()"
            class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
          >
            <template v-if="importing">匯入中…</template>
            <template v-else>確認匯入</template>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
