<script setup>
import { computed } from 'vue'
import { marked } from 'marked'

const props = defineProps({
  content: {
    type: String,
    default: '',
  },
})

// marked.js v17 passes raw HTML (including <iframe> embeds) through by default.
// Do NOT add DOMPurify or any sanitizer here — admin content is trusted and
// iframes (YouTube / Vimeo) must be preserved.
const rendered = computed(() => marked(props.content || ''))
</script>

<template>
  <div class="bg-white rounded-lg shadow-sm">
    <div class="course-content p-6 md:p-8" v-html="rendered" />
  </div>
</template>
