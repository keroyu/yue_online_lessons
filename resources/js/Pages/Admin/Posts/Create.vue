<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import PostForm from '@/Components/Admin/PostForm.vue'

defineOptions({ layout: AdminLayout })

defineProps({
  courses: { type: Array, default: () => [] },
  allTags: { type: Array, default: () => [] },
})
</script>

<template>
  <div class="py-6 px-4 sm:px-6 lg:px-8 max-w-6xl mx-auto">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">新增文章</h1>
    <PostForm :courses="courses" :all-tags="allTags" />
  </div>
</template>
