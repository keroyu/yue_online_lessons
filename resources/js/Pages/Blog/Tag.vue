<script setup>
import { Head, Link } from '@inertiajs/vue3'
import PostCard from '@/Components/Newsletter/PostCard.vue'

defineProps({
  tag: {
    type: Object,
    required: true,
  },
  posts: {
    type: Object,
    required: true,
  },
})
</script>

<template>
  <Head :title="`標籤：${tag.name}`" />

  <div class="max-w-5xl mx-auto px-4 py-8 sm:py-12">
    <header class="mb-8">
      <Link href="/blog" class="text-sm text-gray-400 hover:text-brand-teal">← 回部落格</Link>
      <h1 class="text-3xl font-bold text-gray-900 mt-2">標籤：{{ tag.name }}</h1>
    </header>

    <div v-if="posts.data.length" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
      <PostCard v-for="post in posts.data" :key="post.slug" :post="post" />
    </div>
    <p v-else class="text-gray-400 py-16 text-center">這個標籤還沒有文章。</p>

    <nav v-if="posts.prev_page_url || posts.next_page_url" class="flex justify-between mt-10">
      <Link
        v-if="posts.prev_page_url"
        :href="posts.prev_page_url"
        class="px-4 py-2 border border-gray-200 text-gray-600 hover:bg-gray-50"
      >上一頁</Link>
      <span v-else></span>
      <Link
        v-if="posts.next_page_url"
        :href="posts.next_page_url"
        class="px-4 py-2 border border-gray-200 text-gray-600 hover:bg-gray-50"
      >下一頁</Link>
    </nav>
  </div>
</template>
