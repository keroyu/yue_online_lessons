<script setup>
import { ref, computed } from 'vue'
import { useForm, Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  broadcasts: { type: Object, required: true },
  recentPosts: { type: Array, default: () => [] },
  subscriberCount: { type: Number, default: 0 },
})

const form = useForm({ post_id: null, scheduled_at: '' })

const selectedPost = ref(null)
const searchQuery = ref('')
const searchResults = ref([])
const searching = ref(false)
let searchTimer = null

// Show search results when searching, otherwise the recent 5.
const displayedPosts = computed(() => (searchQuery.value.trim() ? searchResults.value : props.recentPosts))

const doSearch = () => {
  clearTimeout(searchTimer)
  const q = searchQuery.value.trim()
  if (!q) { searchResults.value = []; return }
  searchTimer = setTimeout(async () => {
    searching.value = true
    try {
      const res = await fetch(`/admin/broadcasts/search-posts?q=${encodeURIComponent(q)}`, {
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
      })
      const data = await res.json()
      searchResults.value = data.posts || []
    } finally {
      searching.value = false
    }
  }, 300)
}

const selectPost = (post) => {
  selectedPost.value = post
  form.post_id = post.id
}

const scheduleMode = ref('now')

const send = () => {
  if (!selectedPost.value) return
  const when = scheduleMode.value === 'schedule'
    ? `（排程 ${form.scheduled_at}）`
    : `給 ${props.subscriberCount} 位訂閱者`
  if (!confirm(`確定將「${selectedPost.value.title}」寄出 ${when}？`)) return
  form
    .transform((d) => ({ ...d, scheduled_at: scheduleMode.value === 'schedule' ? d.scheduled_at : '' }))
    .post('/admin/broadcasts', { preserveScroll: true })
}

const goToPage = (page) => {
  router.get('/admin/broadcasts', { page }, { preserveState: true, preserveScroll: true })
}

const statusLabel = (b) => {
  if (b.status === 'scheduled') return `排程 ${b.scheduled_at ?? ''}`
  if (b.status === 'sent') return `已寄 ${b.sent_at ?? ''}`
  return b.status
}
</script>

<template>
  <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">電子報</h1>

    <!-- Send form -->
    <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-lg p-5 mb-8">
      <h2 class="font-semibold text-gray-900 mb-1">把文章寄成電子報</h2>
      <p class="text-sm text-gray-500 mb-4">目前訂閱者：<strong>{{ subscriberCount }}</strong> 人（僅寄給訂閱中的會員）</p>

      <!-- Search -->
      <input
        v-model="searchQuery"
        type="text"
        placeholder="搜尋文章（最近 5 篇之外用這裡找）"
        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm mb-3"
        @input="doSearch"
      />

      <!-- Selectable post list -->
      <p class="text-xs text-gray-400 mb-2">{{ searchQuery.trim() ? (searching ? '搜尋中…' : '搜尋結果') : '最近 5 篇' }}</p>
      <ul class="border border-gray-200 divide-y divide-gray-100 mb-4 max-h-72 overflow-y-auto">
        <li v-for="post in displayedPosts" :key="post.id">
          <button
            type="button"
            class="w-full flex items-center gap-3 px-3 py-2.5 text-left hover:bg-gray-50 cursor-pointer"
            :class="form.post_id === post.id ? 'bg-brand-teal/10' : ''"
            @click="selectPost(post)"
          >
            <span class="w-4 h-4 shrink-0 rounded-full border-2 flex items-center justify-center"
                  :class="form.post_id === post.id ? 'border-brand-teal' : 'border-gray-300'">
              <span v-if="form.post_id === post.id" class="w-2 h-2 rounded-full bg-brand-teal"></span>
            </span>
            <span class="flex-1 min-w-0 truncate text-sm text-gray-900">{{ post.title }}</span>
            <span class="shrink-0 text-xs text-gray-400">{{ post.published_at }}</span>
          </button>
        </li>
        <li v-if="!displayedPosts.length" class="px-3 py-6 text-center text-sm text-gray-400">
          {{ searchQuery.trim() ? '找不到符合的文章' : '尚無已發佈文章' }}
        </li>
      </ul>

      <!-- Timing -->
      <div class="flex flex-wrap items-center gap-4 mb-4">
        <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
          <input v-model="scheduleMode" type="radio" value="now" /> 立即寄送
        </label>
        <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
          <input v-model="scheduleMode" type="radio" value="schedule" /> 排程寄送
        </label>
        <input
          v-if="scheduleMode === 'schedule'"
          v-model="form.scheduled_at"
          type="datetime-local"
          class="rounded-md border border-gray-300 px-3 py-1.5 text-sm"
        />
      </div>
      <p v-if="form.errors.scheduled_at" class="text-sm text-red-600 mb-2">{{ form.errors.scheduled_at }}</p>
      <p v-if="form.errors.post_id" class="text-sm text-red-600 mb-2">{{ form.errors.post_id }}</p>
      <p v-if="subscriberCount === 0" class="text-sm text-amber-600 mb-2">
        目前沒有訂閱者。立即寄送不會送達任何人 —— 可先自己訂閱測試，或用排程等有訂閱者再寄。
      </p>

      <button
        type="button"
        :disabled="!form.post_id || form.processing || (scheduleMode === 'schedule' && !form.scheduled_at)"
        class="bg-brand-teal text-white rounded-md px-6 py-2 font-medium hover:bg-brand-teal/90 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
        @click="send"
      >{{ form.processing ? '處理中…' : (scheduleMode === 'schedule' ? '排程寄送' : '立即寄送') }}</button>
    </div>

    <!-- History -->
    <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-lg overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
          <tr>
            <th class="px-4 py-3 font-medium">主旨</th>
            <th class="px-4 py-3 font-medium">狀態 / 時間</th>
            <th class="px-4 py-3 font-medium text-right">收件</th>
            <th class="px-4 py-3 font-medium text-right">開信率</th>
            <th class="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="b in broadcasts.data" :key="b.id" class="hover:bg-gray-50">
            <td class="px-4 py-3 font-medium text-gray-900">{{ b.subject }}</td>
            <td class="px-4 py-3 text-gray-500">{{ statusLabel(b) }}</td>
            <td class="px-4 py-3 text-right tabular-nums">{{ b.recipients_count }}</td>
            <td class="px-4 py-3 text-right tabular-nums">{{ b.open_rate === null ? '—' : b.open_rate + '%' }}</td>
            <td class="px-4 py-3 text-right">
              <Link v-if="b.status === 'sent' || b.status === 'sending'" :href="`/admin/broadcasts/${b.id}`" class="text-brand-teal hover:underline">詳情</Link>
            </td>
          </tr>
          <tr v-if="!broadcasts.data.length">
            <td colspan="5" class="px-4 py-10 text-center text-gray-400">尚未寄過電子報。</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="broadcasts.last_page > 1" class="flex justify-center gap-2 mt-4">
      <button
        v-for="p in broadcasts.last_page"
        :key="p"
        type="button"
        class="px-3 py-1 border text-sm"
        :class="p === broadcasts.current_page ? 'bg-brand-navy text-white border-brand-navy rounded-md' : 'border-gray-300 hover:bg-gray-50'"
        @click="goToPage(p)"
      >{{ p }}</button>
    </div>
  </div>
</template>
