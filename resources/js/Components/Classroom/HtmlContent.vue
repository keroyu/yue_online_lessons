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
// `breaks: true` so one Enter in the lesson editor is one line break here, the
// same as the drip mail built from this md_content (011 FR-021). Without it the
// two disagreed: the letter broke the line, the classroom joined it.
const rendered = computed(() => marked(props.content || '', { breaks: true }))
</script>

<template>
  <div class="bg-white rounded-lg shadow-sm">
    <div class="course-content p-6 md:p-8" v-html="rendered" />
  </div>
</template>
