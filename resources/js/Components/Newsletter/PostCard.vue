<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
  post: {
    type: Object,
    required: true,
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
  <article class="group bg-white border border-gray-200 overflow-hidden flex flex-col transition-shadow hover:shadow-md">
    <Link :href="post.url" class="block">
      <div v-if="post.cover_url" class="aspect-[16/9] overflow-hidden bg-gray-100">
        <img
          :src="post.cover_url"
          :alt="post.title"
          loading="lazy"
          class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
        />
      </div>
    </Link>

    <div class="p-4 flex flex-col flex-1">
      <div v-if="post.tags && post.tags.length" class="flex flex-wrap gap-1.5 mb-2">
        <Link
          v-for="tag in post.tags"
          :key="tag.slug"
          :href="`/blog/tag/${tag.slug}`"
          class="text-xs text-brand-teal bg-brand-teal/10 px-2 py-0.5 hover:bg-brand-teal/20"
        >
          {{ tag.name }}
        </Link>
      </div>

      <h3 class="text-lg font-semibold text-gray-900 leading-snug line-clamp-2">
        <Link :href="post.url" class="hover:text-brand-teal">{{ post.title }}</Link>
      </h3>

      <p v-if="post.excerpt" class="text-sm text-gray-600 mt-2 line-clamp-3 flex-1">
        {{ post.excerpt }}
      </p>

      <p class="text-xs text-gray-400 mt-3">{{ formatDate(post.published_at) }}</p>
    </div>
  </article>
</template>
