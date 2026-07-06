<script setup>
import SectionHeader from '@/Components/SectionHeader.vue'

defineProps({
  articles: {
    type: Array,
    default: () => [],
  },
})

const formatDate = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('zh-TW', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
}
</script>

<template>
  <div v-if="articles.length > 0" class="bg-white border border-gray-200 p-4">
    <SectionHeader title="近期文章" />
    <ul class="space-y-3">
      <li v-for="article in articles" :key="article.url" class="group">
        <a
          :href="article.url"
          target="_blank"
          rel="noopener noreferrer"
          class="block hover:bg-gray-50 p-2 -mx-2 transition-colors"
        >
          <p class="text-base font-medium text-gray-900 group-hover:text-brand-teal line-clamp-2 leading-snug">
            {{ article.title }}
          </p>
          <p class="text-sm text-gray-500 mt-1">
            {{ formatDate(article.published_at) }}
          </p>
        </a>
      </li>
    </ul>
  </div>
</template>
