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
  isLocallyCompleted: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['select', 'toggleComplete'])

// Check if lesson is locked (drip courses)
const isLocked = computed(() => {
  return props.lesson.is_unlocked === false
})

// Check if lesson appears completed (server state OR optimistic local state)
const showAsCompleted = computed(() => {
  if (isLocked.value) return false
  return props.lesson.is_completed || props.isLocallyCompleted
})

// Check if this is a pending optimistic state (not yet saved to server)
const isPendingCompletion = computed(() => {
  return props.isLocallyCompleted && !props.lesson.is_completed
})

const handleClick = () => {
  if (isLocked.value) return
  emit('select', props.lesson)
}

const handleToggleComplete = (e) => {
  e.stopPropagation()
  if (isLocked.value) return
  emit('toggleComplete', props.lesson)
}
</script>

<template>
  <div
    class="flex items-center gap-3 px-3 py-2 rounded-md transition-colors"
    :class="[
      isLocked ? 'opacity-60 cursor-not-allowed' : 'cursor-pointer',
      isActive && !isLocked ? 'bg-indigo-50 text-indigo-700' : !isLocked ? 'hover:bg-gray-50 text-gray-700' : 'text-gray-400',
    ]"
    @click="handleClick"
  >
    <!-- Lock Icon (drip locked) -->
    <div
      v-if="isLocked"
      class="flex-shrink-0 w-5 h-5 flex items-center justify-center text-gray-400"
    >
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
      </svg>
    </div>
    <!-- Completion/Play Icon -->
    <button
      v-else-if="showAsCompleted"
      type="button"
      class="flex-shrink-0 w-5 h-5 flex items-center justify-center hover:text-green-600"
      :class="isPendingCompletion ? 'text-green-400' : 'text-green-500'"
      :title="isPendingCompletion ? '等待儲存中（5分鐘後）' : '點擊標記為未完成'"
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

    <!-- Unlock countdown (drip locked) -->
    <span v-if="isLocked && lesson.unlock_in_days === -1" class="flex-shrink-0 text-xs text-gray-400 font-medium">
      已鎖定
    </span>
    <span v-else-if="isLocked && lesson.unlock_in_days > 0" class="flex-shrink-0 text-xs text-orange-500 font-medium">
      {{ lesson.unlock_in_days }} 天後解鎖
    </span>
    <!-- Duration -->
    <span v-else class="flex-shrink-0 text-xs text-gray-400">{{ lesson.duration_formatted }}</span>
  </div>
</template>
