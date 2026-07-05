<script setup>
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
    <h3 class="text-lg font-semibold text-brand-navy mb-4 pb-2 border-b border-gray-200 flex items-center gap-2">
      <svg class="w-5 h-5 text-brand-teal" viewBox="0 0 24 24" fill="currentColor">
        <path d="M22.539 8.242H1.46V5.406h21.08v2.836zM1.46 10.812V24L12 18.11 22.54 24V10.812H1.46zM22.54 0H1.46v2.836h21.08V0z"/>
      </svg>
      近期文章
    </h3>
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
