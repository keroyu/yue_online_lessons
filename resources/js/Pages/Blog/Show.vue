<script setup>
import { Head, Link } from '@inertiajs/vue3'
import ShareButtons from '@/Components/Newsletter/ShareButtons.vue'
import PostCard from '@/Components/Newsletter/PostCard.vue'
import SubscribeForm from '@/Components/Newsletter/SubscribeForm.vue'

defineProps({
  post: {
    type: Object,
    required: true,
  },
  related: {
    type: Array,
    default: () => [],
  },
})
</script>

<template>
  <Head :title="post.title" />

  <article class="max-w-3xl mx-auto my-8 sm:my-12 bg-white border border-gray-200 px-5 sm:px-8 py-8 sm:py-10">
    <div v-if="post.tags && post.tags.length" class="flex flex-wrap gap-2 mb-3">
      <Link
        v-for="tag in post.tags"
        :key="tag.slug"
        :href="`/blog/tag/${tag.slug}`"
        class="text-xs text-brand-teal bg-brand-teal/10 px-2 py-0.5 hover:bg-brand-teal/20"
      >{{ tag.name }}</Link>
    </div>

    <h1 class="text-3xl sm:text-4xl font-bold text-gray-900 leading-tight">{{ post.title }}</h1>

    <div class="flex items-center gap-3 text-sm text-gray-400 mt-3">
      <span v-if="post.published_at_human">{{ post.published_at_human }}</span>
      <span>·</span>
      <span>{{ post.view_count }} 次瀏覽</span>
    </div>

    <img
      v-if="post.cover_url"
      :src="post.cover_url"
      :alt="post.title"
      class="w-full mt-6 border border-gray-100"
    />

    <!-- Server-rendered HTML (D4): body ships in initial payload for SEO -->
    <div class="max-w-none mt-8 post-body text-gray-800 leading-relaxed" v-html="post.body_html"></div>

    <div v-if="post.related_course" class="mt-10 border border-gray-200 bg-gray-50 p-5 flex gap-4 items-center">
      <img
        v-if="post.related_course.thumbnail"
        :src="post.related_course.thumbnail"
        :alt="post.related_course.name"
        class="w-24 h-24 object-cover flex-shrink-0"
      />
      <div class="flex-1">
        <p class="text-xs text-gray-400">延伸課程</p>
        <h3 class="font-semibold text-gray-900">{{ post.related_course.name }}</h3>
        <p class="text-sm text-gray-500 line-clamp-2">{{ post.related_course.tagline }}</p>
        <a
          :href="post.related_course.url"
          class="inline-block mt-2 text-sm font-medium text-brand-teal hover:underline"
        >了解課程 →</a>
      </div>
    </div>

    <div class="mt-8 pt-6 border-t border-gray-100">
      <ShareButtons :url="post.url" :title="post.title" />
    </div>

    <div class="mt-10">
      <SubscribeForm source="blog_show" />
    </div>

    <section v-if="related.length" class="mt-12">
      <h2 class="text-xl font-semibold text-gray-900 mb-4">相關文章</h2>
      <div class="grid gap-6 sm:grid-cols-2">
        <PostCard v-for="p in related" :key="p.slug" :post="p" />
      </div>
    </section>
  </article>
</template>

<style scoped>
/* Style the server-rendered v-html body (needs :deep in scoped styles) */
.post-body :deep(h1),
.post-body :deep(h2) {
  font-weight: 700;
  font-size: 1.5rem;
  margin: 1.75rem 0 0.75rem;
}
.post-body :deep(p) {
  margin: 1rem 0;
}
.post-body :deep(ul),
.post-body :deep(ol) {
  margin: 1rem 0;
  padding-left: 1.5rem;
  list-style: revert;
}
.post-body :deep(a) {
  color: #0d9488;
  text-decoration: underline;
}
/* Responsive 16:9 video embed with generous vertical spacing */
.post-body :deep(.video-embed) {
  position: relative;
  padding-bottom: 56.25%;
  height: 0;
  margin: 2.5rem 0;
}
.post-body :deep(.video-embed iframe) {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  border: 0;
}
</style>
