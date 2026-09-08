<script setup>
import { Head } from '@inertiajs/vue3'
import PostCard from '@/Components/Newsletter/PostCard.vue'
import Pagination from '@/Components/Pagination.vue'
import SubscribeForm from '@/Components/Newsletter/SubscribeForm.vue'

defineProps({
  posts: {
    type: Object,
    required: true,
  },
})

const pageHref = (page) => (page === 1 ? '/blog' : `/blog?page=${page}`)
</script>

<template>
  <Head title="部落格" />

  <div class="max-w-5xl mx-auto px-4 py-8 sm:py-12">
    <header class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900">部落格</h1>
      <p class="text-gray-500 mt-2">實用的 Prompt、免費教學短片與輕量筆記。</p>
    </header>

    <div class="mb-10">
      <SubscribeForm source="blog_index" />
    </div>

    <div v-if="posts.data.length" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
      <PostCard v-for="post in posts.data" :key="post.slug" :post="post" />
    </div>
    <p v-else class="text-gray-400 py-16 text-center">目前還沒有文章。</p>

    <!-- href mode: these must stay real links a crawler can follow (FR-031) -->
    <Pagination
      class="mt-10"
      :current-page="posts.current_page"
      :last-page="posts.last_page"
      :href="pageHref"
    />
  </div>
</template>
