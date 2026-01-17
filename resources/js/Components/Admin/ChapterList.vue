<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import draggable from 'vuedraggable'
import LessonForm from './LessonForm.vue'

const props = defineProps({
  courseId: {
    type: Number,
    required: true,
  },
  chapters: {
    type: Array,
    required: true,
  },
  standaloneLessons: {
    type: Array,
    default: () => [],
  },
})

const emit = defineEmits(['update:chapters', 'update:standaloneLessons'])

const localChapters = ref([...props.chapters])
const localStandaloneLessons = ref([...props.standaloneLessons])

// Chapter editing
const editingChapterId = ref(null)
const editingChapterTitle = ref('')
const newChapterTitle = ref('')
const showAddChapter = ref(false)

// Lesson editing
const showLessonForm = ref(false)
const editingLesson = ref(null)
const lessonChapterId = ref(null)

const startEditChapter = (chapter) => {
  editingChapterId.value = chapter.id
  editingChapterTitle.value = chapter.title
}

const cancelEditChapter = () => {
  editingChapterId.value = null
  editingChapterTitle.value = ''
}

const saveChapter = (chapter) => {
  router.put(`/admin/chapters/${chapter.id}`, {
    title: editingChapterTitle.value,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      cancelEditChapter()
    },
  })
}

const addChapter = () => {
  if (!newChapterTitle.value.trim()) return

  router.post(`/admin/courses/${props.courseId}/chapters`, {
    title: newChapterTitle.value,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      newChapterTitle.value = ''
      showAddChapter.value = false
    },
  })
}

const deleteChapter = (chapter) => {
  if (!confirm(`確定要刪除章節「${chapter.title}」及其所有小節嗎？`)) return

  router.delete(`/admin/chapters/${chapter.id}`, {
    preserveScroll: true,
  })
}

// Lesson methods
const openAddLesson = (chapterId = null) => {
  lessonChapterId.value = chapterId
  editingLesson.value = null
  showLessonForm.value = true
}

const openEditLesson = (lesson, chapterId = null) => {
  lessonChapterId.value = chapterId
  editingLesson.value = { ...lesson }
  showLessonForm.value = true
}

const closeLessonForm = () => {
  showLessonForm.value = false
  editingLesson.value = null
  lessonChapterId.value = null
}

const saveLesson = (lessonData) => {
  if (editingLesson.value) {
    router.put(`/admin/lessons/${editingLesson.value.id}`, lessonData, {
      preserveScroll: true,
      onSuccess: closeLessonForm,
    })
  } else {
    router.post(`/admin/courses/${props.courseId}/lessons`, {
      ...lessonData,
      chapter_id: lessonChapterId.value,
    }, {
      preserveScroll: true,
      onSuccess: closeLessonForm,
    })
  }
}

const deleteLesson = (lesson) => {
  if (!confirm(`確定要刪除小節「${lesson.title}」嗎？`)) return

  router.delete(`/admin/lessons/${lesson.id}`, {
    preserveScroll: true,
  })
}

// Drag and drop
const onChapterDragEnd = () => {
  const items = localChapters.value.map((chapter, index) => ({
    id: chapter.id,
    sort_order: index,
  }))

  router.post(`/admin/courses/${props.courseId}/chapters/reorder`, { items }, {
    preserveScroll: true,
    preserveState: true,
  })
}

const onLessonDragEnd = (chapterId = null) => {
  // Collect all lessons with their new order and chapter
  const items = []

  // Add lessons from each chapter
  localChapters.value.forEach((chapter) => {
    chapter.lessons.forEach((lesson, index) => {
      items.push({
        id: lesson.id,
        sort_order: index,
        chapter_id: chapter.id,
      })
    })
  })

  // Add standalone lessons
  localStandaloneLessons.value.forEach((lesson, index) => {
    items.push({
      id: lesson.id,
      sort_order: index,
      chapter_id: null,
    })
  })

  router.post(`/admin/courses/${props.courseId}/lessons/reorder`, { items }, {
    preserveScroll: true,
    preserveState: true,
  })
}
</script>

<template>
  <div class="space-y-6">
    <!-- Chapters -->
    <draggable
      v-model="localChapters"
      item-key="id"
      handle=".chapter-handle"
      ghost-class="opacity-50"
      @end="onChapterDragEnd"
    >
      <template #item="{ element: chapter }">
        <div class="bg-white shadow rounded-lg overflow-hidden">
          <!-- Chapter Header -->
          <div class="bg-gray-50 px-4 py-3 flex items-center justify-between">
            <div class="flex items-center flex-1">
              <button
                type="button"
                class="chapter-handle cursor-move p-1 text-gray-400 hover:text-gray-600 mr-2"
              >
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                </svg>
              </button>

              <template v-if="editingChapterId === chapter.id">
                <input
                  v-model="editingChapterTitle"
                  type="text"
                  class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                  @keyup.enter="saveChapter(chapter)"
                  @keyup.escape="cancelEditChapter"
                />
                <button
                  type="button"
                  class="ml-2 text-green-600 hover:text-green-700"
                  @click="saveChapter(chapter)"
                >
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                  </svg>
                </button>
                <button
                  type="button"
                  class="ml-1 text-gray-400 hover:text-gray-600"
                  @click="cancelEditChapter"
                >
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </template>
              <template v-else>
                <h3 class="text-sm font-medium text-gray-900">{{ chapter.title }}</h3>
                <span class="ml-2 text-xs text-gray-500">({{ chapter.lessons.length }} 小節)</span>
              </template>
            </div>

            <div v-if="editingChapterId !== chapter.id" class="flex items-center space-x-2">
              <button
                type="button"
                class="text-indigo-600 hover:text-indigo-900 text-sm"
                @click="openAddLesson(chapter.id)"
              >
                新增小節
              </button>
              <button
                type="button"
                class="text-gray-400 hover:text-gray-600"
                @click="startEditChapter(chapter)"
              >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
              </button>
              <button
                type="button"
                class="text-red-400 hover:text-red-600"
                @click="deleteChapter(chapter)"
              >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </button>
            </div>
          </div>

          <!-- Lessons -->
          <draggable
            v-model="chapter.lessons"
            item-key="id"
            handle=".lesson-handle"
            group="lessons"
            ghost-class="opacity-50"
            class="divide-y divide-gray-200"
            @end="onLessonDragEnd(chapter.id)"
          >
            <template #item="{ element: lesson }">
              <div class="px-4 py-3 flex items-center justify-between hover:bg-gray-50">
                <div class="flex items-center flex-1">
                  <button
                    type="button"
                    class="lesson-handle cursor-move p-1 text-gray-400 hover:text-gray-600 mr-2"
                  >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                    </svg>
                  </button>

                  <div class="flex items-center">
                    <span v-if="lesson.has_video" class="text-indigo-500 mr-2">
                      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                    </span>
                    <span v-else class="text-gray-400 mr-2">
                      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                      </svg>
                    </span>
                    <span class="text-sm text-gray-900">{{ lesson.title }}</span>
                    <span v-if="lesson.duration_formatted" class="ml-2 text-xs text-gray-500">
                      {{ lesson.duration_formatted }}
                    </span>
                  </div>
                </div>

                <div class="flex items-center space-x-2">
                  <button
                    type="button"
                    class="text-gray-400 hover:text-gray-600"
                    @click="openEditLesson(lesson, chapter.id)"
                  >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                  </button>
                  <button
                    type="button"
                    class="text-red-400 hover:text-red-600"
                    @click="deleteLesson(lesson)"
                  >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>
              </div>
            </template>
          </draggable>

          <div v-if="chapter.lessons.length === 0" class="px-4 py-6 text-center text-sm text-gray-500">
            尚無小節，點擊「新增小節」開始建立
          </div>
        </div>
      </template>
    </draggable>

    <!-- Standalone Lessons -->
    <div v-if="localStandaloneLessons.length > 0" class="bg-white shadow rounded-lg overflow-hidden">
      <div class="bg-gray-50 px-4 py-3">
        <h3 class="text-sm font-medium text-gray-900">獨立小節（無章節分類）</h3>
      </div>

      <draggable
        v-model="localStandaloneLessons"
        item-key="id"
        handle=".lesson-handle"
        group="lessons"
        ghost-class="opacity-50"
        class="divide-y divide-gray-200"
        @end="onLessonDragEnd(null)"
      >
        <template #item="{ element: lesson }">
          <div class="px-4 py-3 flex items-center justify-between hover:bg-gray-50">
            <div class="flex items-center flex-1">
              <button
                type="button"
                class="lesson-handle cursor-move p-1 text-gray-400 hover:text-gray-600 mr-2"
              >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                </svg>
              </button>

              <div class="flex items-center">
                <span v-if="lesson.has_video" class="text-indigo-500 mr-2">
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </span>
                <span v-else class="text-gray-400 mr-2">
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                </span>
                <span class="text-sm text-gray-900">{{ lesson.title }}</span>
                <span v-if="lesson.duration_formatted" class="ml-2 text-xs text-gray-500">
                  {{ lesson.duration_formatted }}
                </span>
              </div>
            </div>

            <div class="flex items-center space-x-2">
              <button
                type="button"
                class="text-gray-400 hover:text-gray-600"
                @click="openEditLesson(lesson, null)"
              >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
              </button>
              <button
                type="button"
                class="text-red-400 hover:text-red-600"
                @click="deleteLesson(lesson)"
              >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </button>
            </div>
          </div>
        </template>
      </draggable>
    </div>

    <!-- Add Chapter Form -->
    <div v-if="showAddChapter" class="bg-white shadow rounded-lg p-4">
      <div class="flex items-center space-x-3">
        <input
          v-model="newChapterTitle"
          type="text"
          placeholder="章節標題"
          class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          @keyup.enter="addChapter"
          @keyup.escape="showAddChapter = false; newChapterTitle = ''"
        />
        <button
          type="button"
          class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700"
          @click="addChapter"
        >
          新增
        </button>
        <button
          type="button"
          class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
          @click="showAddChapter = false; newChapterTitle = ''"
        >
          取消
        </button>
      </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex space-x-3">
      <button
        type="button"
        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700"
        @click="showAddChapter = true"
      >
        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
        </svg>
        新增章節
      </button>
      <button
        type="button"
        class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50"
        @click="openAddLesson(null)"
      >
        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
        </svg>
        新增獨立小節
      </button>
    </div>

    <!-- Lesson Form Modal -->
    <LessonForm
      v-if="showLessonForm"
      :lesson="editingLesson"
      @save="saveLesson"
      @close="closeLessonForm"
    />
  </div>
</template>
