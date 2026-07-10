<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
  posts: {
    type: Array,
    default: () => [],
  },
})

const formatDate = (dateString) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleDateString('zh-TW', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
}
</script>

<template>
  <div v-if="posts.length" class="bg-white border border-gray-200 mb-6">
    <h2 class="px-4 py-3 border-b border-gray-100 font-bold text-gray-900">熱門文章</h2>
    <ul>
      <li
        v-for="post in posts"
        :key="post.url"
        class="border-b border-gray-100 last:border-0"
      >
        <Link
          :href="post.url"
          class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition-colors"
        >
          <span
            v-if="post.tag"
            class="shrink-0 text-xs text-brand-teal bg-brand-teal/10 px-2 py-0.5"
          >{{ post.tag }}</span>

          <span class="min-w-0 max-w-[45%] font-medium text-gray-900 truncate">
            {{ post.title }}
          </span>

          <span class="hidden md:block flex-1 min-w-0 text-sm text-gray-400 truncate">
            {{ post.preview }}
          </span>

          <span class="ml-auto shrink-0 text-sm text-gray-400 whitespace-nowrap">
            {{ formatDate(post.published_at) }}
          </span>
        </Link>
      </li>
    </ul>
  </div>
</template>
