<script setup>
import { Link } from '@inertiajs/vue3'
import SectionHeader from '@/Components/SectionHeader.vue'

defineProps({
  courses: {
    type: Array,
    default: () => [],
  },
})
</script>

<template>
  <div v-if="courses.length > 0" class="bg-white border border-gray-200 p-4">
    <SectionHeader title="精選推薦" />
    <div class="space-y-4">
      <div v-for="course in courses" :key="course.id" class="group">
        <!-- Thumbnail -->
        <Link :href="course.url" class="block aspect-video bg-gray-100 overflow-hidden">
          <img
            v-if="course.thumbnail"
            :src="course.thumbnail"
            :alt="course.name"
            class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
          />
          <div v-else class="w-full h-full flex items-center justify-center text-gray-300">
            <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
            </svg>
          </div>
        </Link>

        <!-- Custom blurb (falls back to course name); shows full text, preserves line breaks -->
        <p class="mt-2 text-sm text-gray-600 leading-relaxed whitespace-pre-line">
          {{ course.blurb || course.name }}
        </p>

        <!-- CTA to sales page -->
        <Link
          :href="course.url"
          class="mt-2 inline-flex w-full items-center justify-center bg-brand-navy px-4 py-2 text-sm font-semibold text-white hover:bg-brand-teal transition-colors"
        >
          立即了解
        </Link>
      </div>
    </div>
  </div>
</template>
