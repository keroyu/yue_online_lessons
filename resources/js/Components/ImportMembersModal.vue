<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import Papa from 'papaparse'

const axios = window.axios

const emit = defineEmits(['close'])

const activeTab = ref('text')

// Text tab state
const emailsText = ref('')

// CSV tab state
const csvRows = ref([])
const csvError = ref(null)
const csvFileInput = ref(null)

// Shared state
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

const handleCsvFileSelect = (event) => {
  const file = event.target.files[0]
  if (!file) return

  csvError.value = null
  csvRows.value = []

  Papa.parse(file, {
    header: false,
    skipEmptyLines: true,
    complete: (parseResult) => {
      const data = parseResult.data
      if (!data || data.length === 0) {
        csvError.value = 'CSV 檔案不含任何資料列'
        return
      }

      if (data[0].length < 3) {
        csvError.value = 'CSV 格式錯誤：至少需要 3 欄（依序為 Email、姓名、電話）'
        return
      }

      const dataRows = data.slice(1)
      if (dataRows.length === 0) {
        csvError.value = 'CSV 檔案不含任何資料列'
        return
      }

      csvRows.value = dataRows
    },
    error: () => {
      csvError.value = 'CSV 解析失敗，請確認檔案格式正確'
    },
  })
}

const handleCsvCancel = () => {
  csvRows.value = []
  csvError.value = null
  if (csvFileInput.value) {
    csvFileInput.value.value = ''
  }
}

const handleCsvImport = async () => {
  if (csvRows.value.length === 0) return

  importing.value = true
  error.value = null
  result.value = null

  const rows = csvRows.value.map((row) => ({
    email: row[0] ?? '',
    real_name: row[1] ?? '',
    phone: row[2] ?? '',
  }))

  try {
    const response = await axios.post('/admin/members/import', { rows })
    result.value = response.data
  } catch (err) {
    if (err.response?.data?.errors?.rows) {
      error.value = err.response.data.errors.rows[0]
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
  <Teleport to="body">
  <div class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex min-h-full items-center justify-center p-4">
      <!-- Backdrop -->
      <div class="fixed inset-0 bg-black/50 transition-opacity" @click="handleClose" />

      <!-- Modal panel -->
      <div class="relative w-full max-w-lg bg-white rounded-lg shadow-xl z-10">
        <!-- Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
          <h2 class="text-lg font-semibold text-gray-900">匯入會員</h2>
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

          <!-- Phone format errors list -->
          <div v-if="result?.phone_format_errors?.length > 0" class="mb-4 p-3 bg-orange-50 border border-orange-200 rounded-lg">
            <p class="text-sm font-medium text-orange-800 mb-1">電話格式有誤（{{ result.phone_format_errors.length }} 筆），電話欄已留空</p>
            <ul class="text-xs text-orange-700 font-mono space-y-0.5 max-h-32 overflow-y-auto">
              <li v-for="email in result.phone_format_errors" :key="email">{{ email }}</li>
            </ul>
          </div>

          <!-- Error -->
          <div v-if="error" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-sm text-red-800">{{ error }}</p>
          </div>

          <!-- Input tabs (hidden after result) -->
          <div v-if="!result">
            <!-- Tab buttons -->
            <div class="flex border-b border-gray-200 mb-4">
              <button
                type="button"
                @click="activeTab = 'text'"
                class="px-4 py-2 text-sm font-medium border-b-2 cursor-pointer -mb-px transition-colors"
                :class="activeTab === 'text'
                  ? 'border-indigo-600 text-indigo-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700'"
              >
                貼上 Email 名單
              </button>
              <button
                type="button"
                @click="activeTab = 'csv'"
                class="px-4 py-2 text-sm font-medium border-b-2 cursor-pointer -mb-px transition-colors"
                :class="activeTab === 'csv'
                  ? 'border-indigo-600 text-indigo-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700'"
              >
                上傳 CSV 檔案
              </button>
            </div>

            <!-- Text tab content -->
            <div v-if="activeTab === 'text'">
              <label class="block text-sm font-medium text-gray-700 mb-1">Email 名單</label>
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

            <!-- CSV tab content -->
            <div v-if="activeTab === 'csv'">
              <div class="mb-3">
                <p class="text-xs text-gray-600 mb-1.5">欄位順序固定（第一列為標題列，自動略過）：</p>
                <table class="w-full text-xs border border-gray-300 rounded overflow-hidden">
                  <thead>
                    <tr class="bg-indigo-50 text-indigo-800 border-b border-gray-300">
                      <th class="px-3 py-2 text-left font-semibold border-r border-gray-300">Email</th>
                      <th class="px-3 py-2 text-left font-semibold border-r border-gray-300">姓名</th>
                      <th class="px-3 py-2 text-left font-semibold">電話</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr class="bg-white text-gray-500 italic">
                      <td class="px-3 py-2 border-r border-gray-200">alice@example.com</td>
                      <td class="px-3 py-2 border-r border-gray-200">王小明</td>
                      <td class="px-3 py-2">0912345678</td>
                    </tr>
                  </tbody>
                </table>
                <p class="text-xs text-gray-400 mt-1">欄位名稱不限；超過 3 欄時忽略多餘欄</p>
              </div>

              <!-- File input (shown when no preview) -->
              <div v-if="csvRows.length === 0">
                <label class="block text-sm font-medium text-gray-700 mb-2">選擇 CSV 檔案</label>
                <input
                  ref="csvFileInput"
                  type="file"
                  accept=".csv"
                  @change="handleCsvFileSelect"
                  class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer"
                  :disabled="importing"
                />
                <div v-if="csvError" class="mt-2 p-2 bg-red-50 border border-red-200 rounded text-xs text-red-700">
                  {{ csvError }}
                </div>
              </div>

              <!-- Preview (shown after successful parse) -->
              <div v-if="csvRows.length > 0" class="border border-gray-200 rounded-lg p-3">
                <p class="text-sm font-medium text-gray-700 mb-2">預覽</p>
                <div class="flex gap-2 mb-2">
                  <span class="px-2 py-1 bg-gray-100 text-xs text-gray-600 rounded">Email</span>
                  <span class="px-2 py-1 bg-gray-100 text-xs text-gray-600 rounded">姓名</span>
                  <span class="px-2 py-1 bg-gray-100 text-xs text-gray-600 rounded">電話</span>
                </div>
                <p class="text-sm text-gray-500">共 {{ csvRows.length }} 筆資料</p>
              </div>
            </div>
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

          <!-- Text tab confirm button -->
          <button
            v-if="!result && activeTab === 'text'"
            type="button"
            @click="handleImport"
            :disabled="importing || !emailsText.trim()"
            class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
          >
            <template v-if="importing">匯入中…</template>
            <template v-else>確認匯入</template>
          </button>

          <!-- CSV tab: cancel preview or confirm import -->
          <template v-if="!result && activeTab === 'csv' && csvRows.length > 0">
            <button
              type="button"
              @click="handleCsvCancel"
              :disabled="importing"
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 cursor-pointer"
            >
              重新選擇
            </button>
            <button
              type="button"
              @click="handleCsvImport"
              :disabled="importing"
              class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
            >
              <template v-if="importing">匯入中…</template>
              <template v-else>確認匯入</template>
            </button>
          </template>
        </div>
      </div>
    </div>
  </div>
  </Teleport>
</template>
