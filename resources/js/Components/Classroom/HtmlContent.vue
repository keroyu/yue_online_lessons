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
      class="course-content p-6 md:p-8"
      v-html="content"
    />
  </div>
</template>
