<script setup>
import { Head, Link } from '@inertiajs/vue3'
import PostCard from '@/Components/Newsletter/PostCard.vue'
import Pagination from '@/Components/Pagination.vue'

const props = defineProps({
  tag: {
    type: Object,
    required: true,
  },
  posts: {
    type: Object,
    required: true,
  },
})

const pageHref = (page) =>
  page === 1 ? `/blog/tag/${props.tag.slug}` : `/blog/tag/${props.tag.slug}?page=${page}`
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

    <!-- href mode: these must stay real links a crawler can follow (FR-031) -->
    <Pagination
      class="mt-10"
      :current-page="posts.current_page"
      :last-page="posts.last_page"
      :href="pageHref"
    />
  </div>
</template>
