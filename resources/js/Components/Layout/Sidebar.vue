<script setup>
import FeaturedCourses from '@/Components/FeaturedCourses.vue'
import SocialLinks from '@/Components/SocialLinks.vue'
import BlogArticles from '@/Components/BlogArticles.vue'

// Shared right-hand sidebar (homepage + blog). Widgets render in admin-defined order.
defineProps({
  sidebarOrder: { type: Array, default: () => ['featured_courses', 'social', 'blog'] },
  featuredCourses: { type: Array, default: () => [] },
  socialLinks: { type: Array, default: () => [] },
  snsProfile: { type: Object, default: null },
  blogArticles: { type: Array, default: () => [] },
})
</script>

<template>
  <aside class="space-y-6">
    <template v-for="widget in sidebarOrder" :key="widget">
      <FeaturedCourses v-if="widget === 'featured_courses'" :courses="featuredCourses" />
      <SocialLinks v-else-if="widget === 'social'" :links="socialLinks" :profile="snsProfile" />
      <BlogArticles v-else-if="widget === 'blog'" :articles="blogArticles" />
    </template>
  </aside>
</template>
