<script setup>
import { ref, computed } from 'vue'
import LessonItem from './LessonItem.vue'

const props = defineProps({
  chapters: {
    type: Array,
    required: true,
  },
  standaloneLessons: {
    type: Array,
    default: () => [],
  },
  currentLessonId: {
    type: Number,
    default: null,
  },
  localCompletedLessons: {
    type: Set,
    default: () => new Set(),
  },
})

// Check if lesson is locally completed (optimistic UI state)
const isLocallyCompleted = (lessonId) => {
  return props.localCompletedLessons.has(lessonId)
}

const emit = defineEmits(['selectLesson', 'toggleComplete'])

// Track collapsed chapters
const collapsedChapters = ref({})

const toggleChapter = (chapterId) => {
  collapsedChapters.value[chapterId] = !collapsedChapters.value[chapterId]
}

const isChapterCollapsed = (chapterId) => {
  return collapsedChapters.value[chapterId] ?? false
}

const handleSelectLesson = (lesson) => {
  emit('selectLesson', lesson)
}

const handleToggleComplete = (lesson) => {
  emit('toggleComplete', lesson)
}

// Calculate chapter progress (includes optimistic local state)
const getChapterProgress = (chapter) => {
  const total = chapter.lessons.length
  const completed = chapter.lessons.filter(l => l.is_completed || isLocallyCompleted(l.id)).length
  return { total, completed }
}
</script>

<template>
  <div class="h-full flex flex-col bg-white">
    <!-- Header -->
    <div class="p-4 border-b border-gray-200">
      <h2 class="text-lg font-semibold text-gray-900">課程內容</h2>
    </div>

    <!-- Content -->
    <div class="flex-1 overflow-y-auto">
      <!-- Chapters -->
      <div v-for="chapter in chapters" :key="chapter.id" class="border-b border-gray-100">
        <!-- Chapter Header -->
        <button
          type="button"
          class="w-full flex items-center justify-between px-4 py-3 text-left hover:bg-gray-50 transition-colors"
          @click="toggleChapter(chapter.id)"
        >
          <div class="flex items-center gap-2">
            <svg
              class="w-4 h-4 text-gray-400 transition-transform"
              :class="{ '-rotate-90': isChapterCollapsed(chapter.id) }"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
            <span class="font-medium text-gray-900">{{ chapter.title }}</span>
          </div>
          <span class="text-xs text-gray-500">
            {{ getChapterProgress(chapter).completed }}/{{ getChapterProgress(chapter).total }}
          </span>
        </button>

        <!-- Lessons -->
        <div
          v-show="!isChapterCollapsed(chapter.id)"
          class="pb-2"
        >
          <LessonItem
            v-for="lesson in chapter.lessons"
            :key="lesson.id"
            :lesson="lesson"
            :is-active="lesson.id === currentLessonId"
            :is-locally-completed="isLocallyCompleted(lesson.id)"
            @select="handleSelectLesson"
            @toggle-complete="handleToggleComplete"
          />
        </div>
      </div>

      <!-- Standalone Lessons -->
      <div v-if="standaloneLessons.length > 0" class="py-2">
        <div class="px-4 py-2">
          <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">其他內容</span>
        </div>
        <LessonItem
          v-for="lesson in standaloneLessons"
          :key="lesson.id"
          :lesson="lesson"
          :is-active="lesson.id === currentLessonId"
          :is-locally-completed="isLocallyCompleted(lesson.id)"
          @select="handleSelectLesson"
          @toggle-complete="handleToggleComplete"
        />
      </div>
    </div>
  </div>
</template>
