<script setup>
import { ref, computed, watch } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import ChapterSidebar from '@/Components/Classroom/ChapterSidebar.vue'
import VideoPlayer from '@/Components/Classroom/VideoPlayer.vue'
import HtmlContent from '@/Components/Classroom/HtmlContent.vue'

const props = defineProps({
  course: {
    type: Object,
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
  currentLesson: {
    type: Object,
    default: null,
  },
  hasContent: {
    type: Boolean,
    default: false,
  },
})

// Current lesson state
const selectedLesson = ref(props.currentLesson)
const sidebarOpen = ref(false)

// Update chapters and standaloneLessons with local completion state
const localChapters = ref(JSON.parse(JSON.stringify(props.chapters)))
const localStandaloneLessons = ref(JSON.parse(JSON.stringify(props.standaloneLessons)))

// Find and update lesson completion status locally
const updateLessonCompletion = (lessonId, isCompleted) => {
  // Update in chapters
  for (const chapter of localChapters.value) {
    const lesson = chapter.lessons.find(l => l.id === lessonId)
    if (lesson) {
      lesson.is_completed = isCompleted
      break
    }
  }

  // Update in standalone lessons
  const standaloneLesson = localStandaloneLessons.value.find(l => l.id === lessonId)
  if (standaloneLesson) {
    standaloneLesson.is_completed = isCompleted
  }

  // Update selected lesson if it's the same
  if (selectedLesson.value && selectedLesson.value.id === lessonId) {
    selectedLesson.value.is_completed = isCompleted
  }
}

// Handle lesson selection
const handleSelectLesson = async (lesson) => {
  // Mark as complete when clicked
  if (!lesson.is_completed) {
    try {
      await fetch(`/member/classroom/${props.course.id}/progress/${lesson.id}`, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
          'Accept': 'application/json',
        },
      })
      updateLessonCompletion(lesson.id, true)
    } catch (error) {
      console.error('Failed to mark lesson complete:', error)
    }
  }

  // Fetch full lesson data
  selectedLesson.value = {
    ...lesson,
    is_completed: true, // Just clicked, so it's completed
  }

  // Close sidebar on mobile
  sidebarOpen.value = false

  // Reload page to get full lesson content
  router.visit(`/member/classroom/${props.course.id}`, {
    only: ['currentLesson'],
    data: { lesson_id: lesson.id },
    preserveState: true,
    preserveScroll: true,
    onSuccess: (page) => {
      if (page.props.currentLesson) {
        selectedLesson.value = page.props.currentLesson
      }
    },
  })
}

// Handle toggle complete
const handleToggleComplete = async (lesson) => {
  const newStatus = !lesson.is_completed

  try {
    const method = newStatus ? 'POST' : 'DELETE'
    await fetch(`/member/classroom/${props.course.id}/progress/${lesson.id}`, {
      method,
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        'Accept': 'application/json',
      },
    })
    updateLessonCompletion(lesson.id, newStatus)
  } catch (error) {
    console.error('Failed to toggle lesson completion:', error)
  }
}

// Toggle mobile sidebar
const toggleSidebar = () => {
  sidebarOpen.value = !sidebarOpen.value
}
</script>

<template>
  <div class="min-h-screen bg-gray-100">
    <Head :title="`${course.name} - 上課中`" />

    <!-- Header -->
    <header class="bg-white shadow-sm sticky top-0 z-30">
      <div class="flex items-center justify-between px-4 h-14">
        <div class="flex items-center gap-3">
          <!-- Mobile menu button -->
          <button
            type="button"
            class="lg:hidden p-2 -ml-2 text-gray-500 hover:text-gray-700"
            @click="toggleSidebar"
          >
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
          </button>

          <!-- Back link -->
          <a
            href="/member/learning"
            class="text-gray-500 hover:text-gray-700"
          >
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
          </a>

          <h1 class="font-semibold text-gray-900 truncate">{{ course.name }}</h1>
        </div>
      </div>
    </header>

    <div class="flex h-[calc(100vh-3.5rem)]">
      <!-- Sidebar - Desktop -->
      <aside class="hidden lg:block w-80 flex-shrink-0 border-r border-gray-200 bg-white overflow-hidden">
        <ChapterSidebar
          :chapters="localChapters"
          :standalone-lessons="localStandaloneLessons"
          :current-lesson-id="selectedLesson?.id"
          @select-lesson="handleSelectLesson"
          @toggle-complete="handleToggleComplete"
        />
      </aside>

      <!-- Sidebar - Mobile Overlay -->
      <div
        v-show="sidebarOpen"
        class="fixed inset-0 z-40 lg:hidden"
      >
        <!-- Backdrop -->
        <div
          class="absolute inset-0 bg-black/50"
          @click="sidebarOpen = false"
        />

        <!-- Sidebar Panel -->
        <aside class="absolute inset-y-0 left-0 w-full max-w-sm bg-white shadow-xl">
          <div class="flex items-center justify-between p-4 border-b">
            <h2 class="font-semibold text-gray-900">課程內容</h2>
            <button
              type="button"
              class="p-2 text-gray-500 hover:text-gray-700"
              @click="sidebarOpen = false"
            >
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          <div class="h-[calc(100%-4rem)] overflow-y-auto">
            <ChapterSidebar
              :chapters="localChapters"
              :standalone-lessons="localStandaloneLessons"
              :current-lesson-id="selectedLesson?.id"
              @select-lesson="handleSelectLesson"
              @toggle-complete="handleToggleComplete"
            />
          </div>
        </aside>
      </div>

      <!-- Main Content -->
      <main class="flex-1 overflow-y-auto">
        <div class="max-w-5xl mx-auto p-4 lg:p-8">
          <!-- No content state -->
          <div
            v-if="!hasContent"
            class="flex flex-col items-center justify-center py-16 text-center"
          >
            <svg class="w-16 h-16 text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            <h2 class="text-xl font-semibold text-gray-900 mb-2">課程內容準備中</h2>
            <p class="text-gray-500">講師正在努力製作課程內容，敬請期待！</p>
          </div>

          <!-- Lesson content -->
          <div v-else-if="selectedLesson">
            <!-- Lesson Title -->
            <h2 class="text-xl lg:text-2xl font-bold text-gray-900 mb-4">
              {{ selectedLesson.title }}
            </h2>

            <!-- Video Player -->
            <div v-if="selectedLesson.has_video" class="mb-6">
              <VideoPlayer
                :embed-url="selectedLesson.embed_url"
                :platform="selectedLesson.video_platform"
                :title="selectedLesson.title"
              />
            </div>

            <!-- HTML Content -->
            <div v-if="selectedLesson.html_content">
              <HtmlContent :content="selectedLesson.html_content" />
            </div>

            <!-- No content for this lesson -->
            <div
              v-if="!selectedLesson.has_video && !selectedLesson.html_content"
              class="bg-white rounded-lg shadow-sm p-8 text-center"
            >
              <svg class="mx-auto w-12 h-12 text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              <p class="text-gray-500">此小節暫無內容</p>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>
</template>
