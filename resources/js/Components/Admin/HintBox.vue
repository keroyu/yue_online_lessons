<script setup>
import { ref } from 'vue'

/**
 * Collapsible help box for admin setting screens.
 *
 * Collapsed by default: these boxes are setup instructions, read once when the
 * feature is first wired up and never again, so leaving them open pushes the
 * fields people actually came for below the fold.
 */
defineProps({
  title: { type: String, required: true },
})

const open = ref(false)
</script>

<template>
  <div class="rounded-lg bg-gray-50 border border-gray-200 text-sm text-gray-600">
    <!-- type="button" matters: this sits inside the settings <form>, and the
         default type would submit it on every toggle. -->
    <button
      type="button"
      class="w-full flex items-center justify-between gap-2 p-3 text-left font-semibold text-gray-700 rounded-lg hover:bg-gray-100 transition-colors"
      :aria-expanded="open"
      @click="open = !open"
    >
      <span>{{ title }}</span>
      <svg
        class="w-4 h-4 shrink-0 text-gray-400 transition-transform"
        :class="open ? 'rotate-180' : ''"
        viewBox="0 0 20 20"
        fill="currentColor"
        aria-hidden="true"
      >
        <path
          fill-rule="evenodd"
          d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z"
          clip-rule="evenodd"
        />
      </svg>
    </button>

    <div v-show="open" class="px-3 pb-3">
      <slot />
    </div>
  </div>
</template>
