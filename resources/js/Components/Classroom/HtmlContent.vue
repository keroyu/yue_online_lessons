<script setup>
defineProps({
  content: {
    type: String,
    default: '',
  },
})
</script>

<template>
  <div class="bg-white rounded-lg shadow-sm">
    <div
      class="prose prose-lg max-w-none p-6 md:p-8
             prose-headings:text-gray-900
             prose-p:text-gray-700
             prose-a:text-indigo-600 prose-a:no-underline hover:prose-a:underline
             prose-img:rounded-lg prose-img:shadow-md
             prose-code:text-indigo-600 prose-code:bg-indigo-50 prose-code:px-1 prose-code:rounded
             prose-pre:bg-gray-900 prose-pre:text-gray-100
             prose-blockquote:border-indigo-500 prose-blockquote:bg-indigo-50 prose-blockquote:py-1"
      v-html="content"
    />
  </div>
</template>
