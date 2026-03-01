<script setup>
import { computed } from 'vue'
import { marked } from 'marked'

const props = defineProps({
  content: {
    type: String,
    default: '',
  },
})

const rendered = computed(() => marked(props.content || ''))
</script>

<template>
  <div class="bg-white rounded-lg shadow-sm">
    <div class="course-content p-6 md:p-8" v-html="rendered" />
  </div>
</template>
