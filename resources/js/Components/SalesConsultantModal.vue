<script setup>
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'

// Use the globally configured axios with CSRF token
const axios = window.axios

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['close'])

const loading = ref(false)
const consultants = ref([])
const search = ref('')
const results = ref([])
const searching = ref(false)
const mutatingId = ref(null)
const error = ref('')
const changed = ref(false)

const fetchData = async (withSearch = false) => {
  const params = withSearch && search.value.trim() ? { search: search.value.trim() } : {}
  const res = await axios.get('/admin/members/sales-consultants', { params })
  consultants.value = res.data.consultants
  results.value = res.data.results
}

watch(() => props.show, async (show) => {
  if (!show) return
  loading.value = true
  error.value = ''
  search.value = ''
  results.value = []
  changed.value = false
  try {
    await fetchData()
  } catch {
    error.value = '載入失敗，請稍後再試'
  } finally {
    loading.value = false
  }
})

const doSearch = async () => {
  if (searching.value) return
  searching.value = true
  error.value = ''
  try {
    await fetchData(true)
  } catch {
    error.value = '搜尋失敗，請稍後再試'
  } finally {
    searching.value = false
  }
}

const setConsultant = async (member, value) => {
  if (mutatingId.value) return
  mutatingId.value = member.id
  error.value = ''
  try {
    await axios.patch(`/admin/members/${member.id}/sales-consultant`, {
      is_sales_consultant: value,
    })
    changed.value = true
    await fetchData(true)
  } catch (e) {
    error.value = e.response?.data?.message || '操作失敗，請稍後再試'
  } finally {
    mutatingId.value = null
  }
}

const close = () => {
  // Refresh the members list badge only when something actually changed.
  if (changed.value) router.reload({ only: ['members'] })
  emit('close')
}

const displayName = (m) => m.real_name || m.nickname || '-'
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition ease-in duration-150"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="show"
        class="fixed inset-0 z-50 overflow-y-auto"
        @click="close"
      >
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black/50 transition-opacity" aria-hidden="true" />

        <!-- Modal container -->
        <div class="flex min-h-full items-center justify-center p-4">
          <div
            v-if="show"
            class="relative bg-white rounded-xl shadow-2xl max-w-lg w-full flex flex-col transform transition-all"
            @click.stop
          >
            <!-- Header -->
            <div class="px-6 py-5 border-b border-gray-200 flex justify-between items-start">
              <div>
                <h2 class="text-xl font-semibold text-gray-900">銷售顧問管理</h2>
                <p class="mt-1 text-sm text-gray-500">被指派者可進入後台管理 Leads 名單與折扣碼</p>
              </div>
              <button type="button" class="text-gray-400 hover:text-gray-600" @click="close">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <div class="px-6 py-5 space-y-5">
              <div v-if="error" class="rounded-lg bg-red-50 border border-red-200 p-3 text-sm text-red-700">
                {{ error }}
              </div>

              <!-- Current consultants -->
              <div>
                <h3 class="text-sm font-semibold text-gray-700 mb-2">目前的銷售顧問（{{ consultants.length }}）</h3>
                <p v-if="loading" class="text-sm text-gray-400">載入中…</p>
                <p v-else-if="consultants.length === 0" class="text-sm text-gray-400">尚未指派任何銷售顧問</p>
                <ul v-else class="space-y-2">
                  <li
                    v-for="c in consultants"
                    :key="c.id"
                    class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 px-3 py-2"
                  >
                    <div class="min-w-0">
                      <p class="text-sm text-gray-900 truncate">{{ c.email }}</p>
                      <p class="text-xs text-gray-500">{{ displayName(c) }}</p>
                    </div>
                    <button
                      type="button"
                      :disabled="mutatingId === c.id"
                      class="shrink-0 text-sm text-gray-400 hover:text-red-500 transition-colors disabled:opacity-50"
                      @click="setConsultant(c, false)"
                    >
                      移除
                    </button>
                  </li>
                </ul>
              </div>

              <!-- Search & assign -->
              <div class="border-t border-gray-100 pt-4">
                <h3 class="text-sm font-semibold text-gray-700 mb-2">新增顧問</h3>
                <div class="flex gap-2">
                  <input
                    v-model="search"
                    type="text"
                    placeholder="搜尋 Email 或姓名"
                    class="flex-1 min-w-0 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-brand-teal focus:ring-1 focus:ring-brand-teal outline-none"
                    @keyup.enter="doSearch"
                  />
                  <button
                    type="button"
                    :disabled="searching || !search.trim()"
                    class="px-4 py-2 rounded-lg font-semibold text-sm bg-brand-navy text-white hover:bg-brand-navy/90 transition-colors disabled:opacity-40 shrink-0"
                    @click="doSearch"
                  >
                    搜尋
                  </button>
                </div>

                <ul v-if="results.length > 0" class="mt-3 space-y-2">
                  <li
                    v-for="r in results"
                    :key="r.id"
                    class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 px-3 py-2"
                  >
                    <div class="min-w-0">
                      <p class="text-sm text-gray-900 truncate">{{ r.email }}</p>
                      <p class="text-xs text-gray-500">{{ displayName(r) }}</p>
                    </div>
                    <button
                      type="button"
                      :disabled="mutatingId === r.id"
                      class="shrink-0 px-3 py-1 rounded-lg text-sm font-medium bg-brand-teal/10 text-brand-teal hover:bg-brand-teal/20 transition-colors disabled:opacity-50"
                      @click="setConsultant(r, true)"
                    >
                      指派
                    </button>
                  </li>
                </ul>
                <p v-else-if="!searching && search && results.length === 0" class="mt-3 text-sm text-gray-400">
                  沒有符合的會員（已是顧問者不會出現在結果中）
                </p>
              </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 border-t border-gray-100 flex justify-end">
              <button
                type="button"
                class="px-4 py-2 rounded-lg text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 transition-colors"
                @click="close"
              >
                關閉
              </button>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
