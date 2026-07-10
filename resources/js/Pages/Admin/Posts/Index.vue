<script setup>
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  posts: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
})

const search = ref(props.filters.search ?? '')
const status = ref(props.filters.status ?? '')

const statusLabels = { draft: '草稿', scheduled: '排程', published: '已發佈' }
const statusClass = {
  draft: 'bg-gray-100 text-gray-600',
  scheduled: 'bg-amber-100 text-amber-700',
  published: 'bg-green-100 text-green-700',
}

const applyFilters = () => {
  router.get('/admin/posts', { search: search.value || undefined, status: status.value || undefined }, {
    preserveState: true, preserveScroll: true, replace: true,
  })
}

const sortByViews = () => {
  router.get('/admin/posts', { search: search.value || undefined, status: status.value || undefined, sort: 'views' }, {
    preserveState: true, preserveScroll: true, replace: true,
  })
}

const destroy = (post) => {
  if (!confirm(`確定刪除文章「${post.title}」？（軟刪除，前台立即下架）`)) return
  router.delete(`/admin/posts/${post.id}`, { preserveScroll: true })
}

const goToPage = (page) => {
  router.get('/admin/posts', { ...props.filters, page }, { preserveState: true, preserveScroll: true })
}
</script>

<template>
  <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-6xl mx-auto">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-gray-900">文章管理</h1>
      <Link href="/admin/posts/create" class="bg-brand-teal text-white px-4 py-2 font-medium hover:bg-brand-teal/90">+ 新增文章</Link>
    </div>

    <div class="flex flex-wrap gap-3 mb-4">
      <input v-model="search" type="text" placeholder="搜尋標題 / slug" class="border border-gray-300 px-3 py-2 text-sm" @keyup.enter="applyFilters" />
      <select v-model="status" class="border border-gray-300 px-3 py-2 text-sm" @change="applyFilters">
        <option value="">全部狀態</option>
        <option value="draft">草稿</option>
        <option value="scheduled">排程</option>
        <option value="published">已發佈</option>
      </select>
      <button type="button" class="border border-gray-300 px-3 py-2 text-sm hover:bg-gray-50" @click="applyFilters">篩選</button>
    </div>

    <div class="bg-white border border-gray-200 overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 text-left">
          <tr>
            <th class="px-4 py-3 font-medium">標題</th>
            <th class="px-4 py-3 font-medium">狀態</th>
            <th class="px-4 py-3 font-medium text-right cursor-pointer hover:text-brand-teal" @click="sortByViews">瀏覽 ↓</th>
            <th class="px-4 py-3 font-medium">發佈時間</th>
            <th class="px-4 py-3 font-medium text-right">操作</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="post in posts.data" :key="post.id" class="hover:bg-gray-50">
            <td class="px-4 py-3">
              <span class="font-medium text-gray-900">{{ post.title }}</span>
              <span v-if="post.is_featured" class="ml-2 text-xs text-amber-600">★ 精選</span>
              <div class="text-xs text-gray-400 font-mono">/blog/{{ post.slug }}</div>
            </td>
            <td class="px-4 py-3">
              <span class="text-xs px-2 py-0.5" :class="statusClass[post.status]">{{ statusLabels[post.status] }}</span>
            </td>
            <td class="px-4 py-3 text-right tabular-nums text-gray-600">{{ post.view_count }}</td>
            <td class="px-4 py-3 text-gray-500">{{ post.published_at ?? '—' }}</td>
            <td class="px-4 py-3 text-right whitespace-nowrap">
              <Link :href="`/admin/posts/${post.id}/edit`" class="text-brand-teal hover:underline">編輯</Link>
              <button type="button" class="ml-3 text-red-600 hover:underline cursor-pointer" @click="destroy(post)">刪除</button>
            </td>
          </tr>
          <tr v-if="!posts.data.length">
            <td colspan="5" class="px-4 py-10 text-center text-gray-400">尚無文章。</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="posts.last_page > 1" class="flex justify-center gap-2 mt-4">
      <button
        v-for="p in posts.last_page"
        :key="p"
        type="button"
        class="px-3 py-1 border text-sm"
        :class="p === posts.current_page ? 'bg-brand-navy text-white border-brand-navy' : 'border-gray-300 hover:bg-gray-50'"
        @click="goToPage(p)"
      >{{ p }}</button>
    </div>
  </div>
</template>
