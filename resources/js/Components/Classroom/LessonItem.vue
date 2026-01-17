<script setup>
import { computed } from 'vue'

const props = defineProps({
  lesson: {
    type: Object,
    required: true,
  },
  isActive: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['select', 'toggleComplete'])

const handleClick = () => {
  emit('select', props.lesson)
}

const handleToggleComplete = (e) => {
  e.stopPropagation()
  emit('toggleComplete', props.lesson)
}
</script>

<template>
  <div
    class="flex items-center gap-3 px-3 py-2 rounded-md cursor-pointer transition-colors"
    :class="isActive ? 'bg-indigo-50 text-indigo-700' : 'hover:bg-gray-50 text-gray-700'"
    @click="handleClick"
  >
    <!-- Completion/Play Icon -->
    <button
      v-if="lesson.is_completed"
      type="button"
      class="flex-shrink-0 w-5 h-5 flex items-center justify-center text-green-500 hover:text-green-600"
      title="點擊標記為未完成"
      @click="handleToggleComplete"
    >
      <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
      </svg>
    </button>
    <div
      v-else
      class="flex-shrink-0 w-5 h-5 flex items-center justify-center text-gray-400"
    >
      <svg v-if="lesson.has_video" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" />
      </svg>
      <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
      </svg>
    </div>

    <!-- Title -->
    <span class="flex-1 text-sm truncate">{{ lesson.title }}</span>

    <!-- Duration -->
    <span class="flex-shrink-0 text-xs text-gray-400">{{ lesson.duration_formatted }}</span>
  </div>
</template>
