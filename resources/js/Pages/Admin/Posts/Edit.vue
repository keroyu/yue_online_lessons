<script setup>
import { Link } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PostForm from '@/Components/Admin/PostForm.vue'

defineOptions({ layout: AdminLayout })

defineProps({
  post: { type: Object, required: true },
  courses: { type: Array, default: () => [] },
  images: { type: Array, default: () => [] },
  allTags: { type: Array, default: () => [] },
})
</script>

<template>
  <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-6xl mx-auto">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-gray-900">編輯文章</h1>
      <div class="flex items-center gap-4 text-sm">
        <span class="text-gray-400">{{ post.view_count }} 次瀏覽</span>
        <a v-if="post.status === 'published'" :href="`/blog/${post.slug}`" target="_blank" class="text-brand-teal hover:underline">前台檢視 ↗</a>
      </div>
    </div>
    <PostForm :post="post" :courses="courses" :images="images" :all-tags="allTags" />
  </div>
</template>
