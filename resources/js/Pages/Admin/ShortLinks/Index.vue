<script setup>
import { ref, computed } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  links: {
    type: Array,
    required: true,
  },
  baseUrl: {
    type: String,
    required: true,
  },
})

const fullUrl = (slug) => `${props.baseUrl}/${slug}`

// ── Create ──
const createForm = useForm({
  slug: '',
  target_url: '',
  name: '',
  is_active: true,
})

const submitCreate = () => {
  createForm.post('/admin/short-links', {
    preserveScroll: true,
    onSuccess: () => createForm.reset(),
  })
}

// ── Inline edit ──
const editingId = ref(null)
const editForm = useForm({
  slug: '',
  target_url: '',
  name: '',
  is_active: true,
})

const startEdit = (link) => {
  editingId.value = link.id
  editForm.clearErrors()
  editForm.slug = link.slug
  editForm.target_url = link.target_url
  editForm.name = link.name || ''
  editForm.is_active = link.is_active
}

const cancelEdit = () => {
  editingId.value = null
  editForm.clearErrors()
}

const submitEdit = (link) => {
  editForm.put(`/admin/short-links/${link.id}`, {
    preserveScroll: true,
    onSuccess: () => { editingId.value = null },
  })
}

const toggleActive = (link) => {
  router.put(`/admin/short-links/${link.id}`, {
    slug: link.slug,
    target_url: link.target_url,
    name: link.name,
    is_active: !link.is_active,
  }, { preserveScroll: true })
}

const destroy = (link) => {
  if (!confirm(`確定刪除短網址「/${link.slug}」？刪除後這個連結立即失效，累計點擊數也會一併消失。`)) return
  router.delete(`/admin/short-links/${link.id}`, { preserveScroll: true })
}

// ── Copy to clipboard ──
const copiedId = ref(null)

const copy = async (link) => {
  const url = fullUrl(link.slug)
  try {
    await navigator.clipboard.writeText(url)
  } catch {
    // Older browsers / non-secure contexts
    const el = document.createElement('textarea')
    el.value = url
    document.body.appendChild(el)
    el.select()
    document.execCommand('copy')
    document.body.removeChild(el)
  }
  copiedId.value = link.id
  setTimeout(() => { copiedId.value = null }, 2000)
}

const totalClicks = computed(() => props.links.reduce((sum, l) => sum + l.clicks, 0))
</script>

<template>
  <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
    <div class="sm:flex sm:items-center sm:justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">短網址</h1>
        <p class="mt-1 text-sm text-gray-500">
          用自己的網址轉到外部連結（預約表單、會議室…）。以後換連結只要改這裡，對外公布的網址不用動。
        </p>
      </div>
      <p v-if="links.length" class="mt-2 sm:mt-0 text-sm text-gray-500">
        共 {{ links.length }} 組 · 累計 {{ totalClicks }} 次點擊
      </p>
    </div>

    <!-- 新增 -->
    <form
      class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-lg p-4 mb-6"
      @submit.prevent="submitCreate"
    >
      <h2 class="text-sm font-semibold text-gray-900 mb-3">新增短網址</h2>
      <div class="grid grid-cols-1 sm:grid-cols-12 gap-3">
        <div class="sm:col-span-3">
          <label class="block text-xs font-medium text-gray-500 mb-1">短網址代稱</label>
          <div class="flex items-center rounded-lg border border-gray-300 focus-within:border-brand-teal focus-within:ring-1 focus-within:ring-brand-teal overflow-hidden">
            <span class="pl-3 pr-1 text-sm text-gray-400 shrink-0">/</span>
            <input
              v-model="createForm.slug"
              type="text"
              placeholder="1v1"
              maxlength="64"
              class="block w-full border-0 py-2 pr-3 text-sm focus:ring-0"
            />
          </div>
          <p v-if="createForm.errors.slug" class="mt-1 text-xs text-red-600">{{ createForm.errors.slug }}</p>
        </div>
        <div class="sm:col-span-5">
          <label class="block text-xs font-medium text-gray-500 mb-1">目標網址</label>
          <input
            v-model="createForm.target_url"
            type="url"
            placeholder="https://calendar.app.google/..."
            class="block w-full rounded-lg border-gray-300 py-2 px-3 text-sm focus:border-brand-teal focus:ring-brand-teal"
          />
          <p v-if="createForm.errors.target_url" class="mt-1 text-xs text-red-600">{{ createForm.errors.target_url }}</p>
        </div>
        <div class="sm:col-span-3">
          <label class="block text-xs font-medium text-gray-500 mb-1">備註（選填）</label>
          <input
            v-model="createForm.name"
            type="text"
            placeholder="1對1諮詢預約"
            maxlength="100"
            class="block w-full rounded-lg border-gray-300 py-2 px-3 text-sm focus:border-brand-teal focus:ring-brand-teal"
          />
        </div>
        <div class="sm:col-span-1 flex items-end">
          <button
            type="submit"
            :disabled="createForm.processing"
            class="w-full inline-flex justify-center items-center px-4 py-2 rounded-lg bg-brand-teal text-white text-sm font-semibold hover:bg-brand-teal/80 transition-colors cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
          >
            新增
          </button>
        </div>
      </div>
    </form>

    <!-- 列表 -->
    <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-lg overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-100">
        <thead class="bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
          <tr>
            <th class="px-4 py-3 text-left">短網址</th>
            <th class="px-4 py-3 text-left">目標網址</th>
            <th class="px-4 py-3 text-left">備註</th>
            <th class="px-4 py-3 text-right">點擊</th>
            <th class="px-4 py-3 text-left">最後點擊</th>
            <th class="px-4 py-3 text-left">狀態</th>
            <th class="relative px-4 py-3"><span class="sr-only">操作</span></th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-100">
          <template v-for="link in links" :key="link.id">
            <!-- 檢視列 -->
            <tr v-if="editingId !== link.id" :class="{ 'opacity-60': !link.is_active }">
              <td class="px-4 py-4 text-sm font-medium text-gray-900 whitespace-nowrap">
                <div class="flex items-center gap-2">
                  <span>/{{ link.slug }}</span>
                  <button
                    type="button"
                    class="text-xs text-gray-400 hover:text-brand-teal transition-colors cursor-pointer"
                    :title="`複製 ${fullUrl(link.slug)}`"
                    @click="copy(link)"
                  >
                    {{ copiedId === link.id ? '已複製' : '複製' }}
                  </button>
                </div>
              </td>
              <td class="px-4 py-4 text-sm text-gray-500 max-w-xs truncate">
                <a :href="link.target_url" target="_blank" rel="noopener noreferrer" class="hover:text-brand-teal hover:underline">
                  {{ link.target_url }}
                </a>
              </td>
              <td class="px-4 py-4 text-sm text-gray-500">{{ link.name || '—' }}</td>
              <td class="px-4 py-4 text-sm text-gray-900 text-right tabular-nums">{{ link.clicks }}</td>
              <td class="px-4 py-4 text-sm text-gray-500 whitespace-nowrap">{{ link.last_clicked_at || '—' }}</td>
              <td class="px-4 py-4 text-sm">
                <button
                  type="button"
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium transition-colors cursor-pointer"
                  :class="link.is_active
                    ? 'bg-green-100 text-green-800 hover:bg-green-200'
                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                  @click="toggleActive(link)"
                >
                  {{ link.is_active ? '啟用中' : '已停用' }}
                </button>
              </td>
              <td class="px-4 py-4 text-right text-sm font-medium whitespace-nowrap">
                <button type="button" class="text-brand-teal hover:text-brand-navy transition-colors cursor-pointer" @click="startEdit(link)">
                  編輯
                </button>
                <button type="button" class="ml-3 text-red-600 hover:text-red-800 transition-colors cursor-pointer" @click="destroy(link)">
                  刪除
                </button>
              </td>
            </tr>

            <!-- 編輯列 -->
            <tr v-else class="bg-brand-cream/40">
              <td class="px-4 py-4">
                <div class="flex items-center rounded-lg border border-gray-300 bg-white overflow-hidden">
                  <span class="pl-2 text-sm text-gray-400 shrink-0">/</span>
                  <input v-model="editForm.slug" type="text" maxlength="64" class="block w-28 border-0 py-1.5 pr-2 text-sm focus:ring-0" />
                </div>
                <p v-if="editForm.errors.slug" class="mt-1 text-xs text-red-600">{{ editForm.errors.slug }}</p>
              </td>
              <td class="px-4 py-4">
                <input v-model="editForm.target_url" type="url" class="block w-full min-w-[16rem] rounded-lg border-gray-300 py-1.5 px-2 text-sm focus:border-brand-teal focus:ring-brand-teal" />
                <p v-if="editForm.errors.target_url" class="mt-1 text-xs text-red-600">{{ editForm.errors.target_url }}</p>
              </td>
              <td class="px-4 py-4">
                <input v-model="editForm.name" type="text" maxlength="100" class="block w-full rounded-lg border-gray-300 py-1.5 px-2 text-sm focus:border-brand-teal focus:ring-brand-teal" />
              </td>
              <td class="px-4 py-4 text-sm text-gray-400 text-right tabular-nums">{{ link.clicks }}</td>
              <td class="px-4 py-4 text-sm text-gray-400 whitespace-nowrap">{{ link.last_clicked_at || '—' }}</td>
              <td class="px-4 py-4">
                <label class="inline-flex items-center gap-1.5 text-sm text-gray-600 cursor-pointer">
                  <input v-model="editForm.is_active" type="checkbox" class="rounded border-gray-300 text-brand-teal focus:ring-brand-teal cursor-pointer" />
                  啟用
                </label>
              </td>
              <td class="px-4 py-4 text-right text-sm font-medium whitespace-nowrap">
                <button
                  type="button"
                  :disabled="editForm.processing"
                  class="text-brand-teal hover:text-brand-navy transition-colors cursor-pointer disabled:opacity-50"
                  @click="submitEdit(link)"
                >
                  儲存
                </button>
                <button type="button" class="ml-3 text-gray-500 hover:text-gray-700 transition-colors cursor-pointer" @click="cancelEdit">
                  取消
                </button>
              </td>
            </tr>
          </template>

          <tr v-if="links.length === 0">
            <td colspan="7" class="px-6 py-10 text-center text-sm text-gray-500">
              還沒有短網址。用上面的表單建立第一組，例如 <span class="font-medium text-gray-700">/1v1</span> 指到你的預約頁。
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
