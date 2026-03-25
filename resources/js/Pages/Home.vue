<script setup>
import { Head } from '@inertiajs/vue3'
import CourseCard from '@/Components/CourseCard.vue'
import SocialLinks from '@/Components/SocialLinks.vue'
import BlogArticles from '@/Components/BlogArticles.vue'

defineProps({
  courses: {
    type: Array,
    required: true,
  },
  hero: {
    type: Object,
    default: () => ({
      title: null,
      description: null,
      button_label: null,
      button_url: null,
      banner_url: null,
    }),
  },
  socialLinks: {
    type: Array,
    default: () => [],
  },
  blogArticles: {
    type: Array,
    default: () => [],
  },
  isAdmin: {
    type: Boolean,
    default: false,
  },
})
</script>

<template>
  <Head title="首頁" />

  <div class="py-8 sm:py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

      <!-- Hero section -->
      <div class="relative mb-10 sm:mb-16 rounded-xl overflow-hidden group">
        <!-- Hover overlay: darkens image on hover -->
        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors duration-300 z-10 pointer-events-none" />

        <!-- Banner image -->
        <img
          v-if="hero.banner_url"
          :src="hero.banner_url"
          alt=""
          class="w-full h-[200px] sm:h-[300px] lg:h-[400px] object-cover"
        />
        <!-- Fallback: solid colour when no image -->
        <div v-else class="h-[160px] sm:h-[240px] bg-brand-navy" />

        <!-- Text block: bottom-left -->
        <div class="absolute inset-x-0 bottom-0 z-20 p-4 sm:p-6 space-y-2">
          <!-- Title: white on solid black strip -->
          <h1
            v-if="hero.title"
            class="inline-block bg-black px-2 py-1 text-2xl sm:text-4xl font-bold text-white leading-snug"
          >
            {{ hero.title }}
          </h1>
          <!-- Description: white with drop shadow -->
          <p
            v-if="hero.description"
            class="text-sm sm:text-base text-white max-w-2xl whitespace-pre-line drop-shadow-[0_1px_3px_rgba(0,0,0,0.8)]"
          >
            {{ hero.description }}
          </p>
        </div>

        <!-- CTA button: bottom-right, only when both label and URL are set -->
        <a
          v-if="hero.button_url && hero.button_label"
          :href="hero.button_url"
          target="_blank"
          rel="noopener noreferrer"
          class="absolute bottom-4 right-4 sm:bottom-6 sm:right-6 z-20 px-5 py-2 border border-white/80 bg-white/20 hover:bg-white text-white hover:text-brand-navy text-sm font-semibold transition-all duration-300"
        >
          {{ hero.button_label }}
        </a>
      </div>

      <!-- Main content with sidebar layout -->
      <div class="grid grid-cols-1 lg:grid-cols-[1fr_300px] gap-8">
        <!-- Main area: Courses -->
        <div>
          <div v-if="courses.length > 0">
            <h2 class="text-xl font-semibold text-brand-navy mb-6">所有課程</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
              <CourseCard
                v-for="course in courses"
                :key="course.id"
                :course="course"
                :show-status-badge="isAdmin"
              />
            </div>
          </div>

          <div v-else class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">目前沒有課程</h3>
            <p class="mt-1 text-sm text-gray-500">敬請期待新課程上架！</p>
          </div>
        </div>

        <!-- Sidebar: Social Links + Blog Articles -->
        <aside class="space-y-6">
          <SocialLinks :links="socialLinks" />
          <BlogArticles :articles="blogArticles" />
        </aside>
      </div>

    </div>
  </div>
</template>
