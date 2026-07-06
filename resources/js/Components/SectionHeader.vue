<script setup>
defineProps({
  title: {
    type: String,
    required: true,
  },
})
</script>

<template>
  <!-- Unified section header: teal label block + dotted fill + navy underline -->
  <div class="flex items-stretch border-b-2 border-brand-navy mb-5">
    <span class="shrink-0 bg-brand-teal text-white font-bold px-4 py-2 text-base tracking-wider">
      {{ title }}
    </span>
    <span class="section-dots flex-1 min-w-0"></span>
    <!-- Optional right-aligned content (e.g. filter badges) -->
    <div v-if="$slots.right" class="shrink-0 flex items-center pl-2">
      <slot name="right" />
    </div>
  </div>
</template>
