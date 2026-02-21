<script setup>
import { ref, computed, watch, onUnmounted } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import ChapterSidebar from '@/Components/Classroom/ChapterSidebar.vue'
import VideoPlayer from '@/Components/Classroom/VideoPlayer.vue'
import HtmlContent from '@/Components/Classroom/HtmlContent.vue'
import LessonPromoBlock from '@/Components/Classroom/LessonPromoBlock.vue'
import VideoAccessNotice from '@/Components/Classroom/VideoAccessNotice.vue'

// Throttling: 2-minute threshold before marking lesson as complete on server
const COMPLETION_THRESHOLD_MS = 2 * 60 * 1000

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
  dripSubscription: {
    type: Object,
    default: null,
  },
  videoAccessTargetCourses: {
    type: Array,
    default: () => [],
  },
  rewardDelaySeconds: {
    type: Number,
    default: null,
  },
})

// Current lesson state
const selectedLesson = ref(props.currentLesson)
const sidebarOpen = ref(false)

// Throttling state
const completionTimers = ref({}) // Track setTimeout handles per lesson ID
const localCompletedLessons = ref(new Set()) // Optimistic UI state

// Update chapters and standaloneLessons with local completion state
const localChapters = ref(JSON.parse(JSON.stringify(props.chapters)))
const localStandaloneLessons = ref(JSON.parse(JSON.stringify(props.standaloneLessons)))

// Check if lesson is completed (server state OR optimistic local state)
const isLessonCompleted = (lessonId) => {
  // Check local optimistic state first
  if (localCompletedLessons.value.has(lessonId)) {
    return true
  }

  // Check in chapters
  for (const chapter of localChapters.value) {
    const lesson = chapter.lessons.find(l => l.id === lessonId)
    if (lesson) {
      return lesson.is_completed
    }
  }

  // Check in standalone lessons
  const standaloneLesson = localStandaloneLessons.value.find(l => l.id === lessonId)
  if (standaloneLesson) {
    return standaloneLesson.is_completed
  }

  return false
}

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

// Cancel timer for a specific lesson
const cancelLessonTimer = (lessonId) => {
  if (completionTimers.value[lessonId]) {
    clearTimeout(completionTimers.value[lessonId])
    delete completionTimers.value[lessonId]
  }
}

// Cancel all pending timers
const cancelAllTimers = () => {
  Object.keys(completionTimers.value).forEach(lessonId => {
    clearTimeout(completionTimers.value[lessonId])
  })
  completionTimers.value = {}
}

// Clear all timers on component unmount
onUnmounted(() => {
  cancelAllTimers()
})

// Handle lesson selection with throttling
const handleSelectLesson = async (lesson) => {
  // Skip if already selected
  if (selectedLesson.value?.id === lesson.id) {
    sidebarOpen.value = false
    return
  }

  // Cancel timer for previous lesson (if switching before 5 minutes)
  if (selectedLesson.value) {
    cancelLessonTimer(selectedLesson.value.id)
    // Remove from local optimistic state if not yet persisted
    if (!selectedLesson.value.is_completed) {
      localCompletedLessons.value.delete(selectedLesson.value.id)
    }
  }

  // Optimistic update: add to local completed set immediately
  // Only if not already completed on server
  if (!lesson.is_completed && !localCompletedLessons.value.has(lesson.id)) {
    localCompletedLessons.value.add(lesson.id)
  }

  // Start 5-minute timer for new lesson (if not already completed on server)
  if (!lesson.is_completed) {
    // Cancel existing timer for this lesson (in case of rapid re-selection)
    cancelLessonTimer(lesson.id)

    completionTimers.value[lesson.id] = setTimeout(async () => {
      try {
        await fetch(`/member/classroom/${props.course.id}/progress/${lesson.id}`, {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
          },
        })
        // Update server state after successful POST
        updateLessonCompletion(lesson.id, true)
        delete completionTimers.value[lesson.id]
      } catch (error) {
        console.error('Failed to mark lesson complete:', error)
      }
    }, COMPLETION_THRESHOLD_MS)
  }

  // Close sidebar on mobile
  sidebarOpen.value = false

  // Reload page to get full lesson content (don't update selectedLesson until we have full data)
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

// Handle toggle complete (mark as incomplete is immediate, no throttling)
const handleToggleComplete = async (lesson) => {
  const isCurrentlyCompleted = lesson.is_completed || localCompletedLessons.value.has(lesson.id)
  const newStatus = !isCurrentlyCompleted

  if (!newStatus) {
    // Marking as incomplete: immediate, no throttling
    // Cancel any pending timer for this lesson
    cancelLessonTimer(lesson.id)
    // Remove from local optimistic state
    localCompletedLessons.value.delete(lesson.id)

    try {
      await fetch(`/member/classroom/${props.course.id}/progress/${lesson.id}`, {
        method: 'DELETE',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
          'Accept': 'application/json',
        },
      })
      updateLessonCompletion(lesson.id, false)
    } catch (error) {
      console.error('Failed to mark lesson incomplete:', error)
    }
  } else {
    // Marking as complete: add to local state, start timer
    localCompletedLessons.value.add(lesson.id)

    // Start 5-minute timer if not already completed on server
    if (!lesson.is_completed) {
      cancelLessonTimer(lesson.id)

      completionTimers.value[lesson.id] = setTimeout(async () => {
        try {
          await fetch(`/member/classroom/${props.course.id}/progress/${lesson.id}`, {
            method: 'POST',
            headers: {
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
              'Accept': 'application/json',
            },
          })
          updateLessonCompletion(lesson.id, true)
          delete completionTimers.value[lesson.id]
        } catch (error) {
          console.error('Failed to mark lesson complete:', error)
        }
      }, COMPLETION_THRESHOLD_MS)
    }
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
          :local-completed-lessons="localCompletedLessons"
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
              :local-completed-lessons="localCompletedLessons"
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

            <!-- Has video: Countdown → Promo → Content (strike while hot) -->
            <template v-if="selectedLesson.has_video">
              <!-- Video Access Notice (drip courses only) -->
              <VideoAccessNotice
                v-if="course.is_drip
                  && selectedLesson?.video_id
                  && dripSubscription?.status !== 'converted'
                  && (selectedLesson.video_access_expired || selectedLesson.video_access_remaining_seconds > 0)"
                :key="'video-access-' + selectedLesson.id"
                :expired="selectedLesson.video_access_expired"
                :remaining-seconds="selectedLesson.video_access_remaining_seconds"
                :target-courses="videoAccessTargetCourses"
                :reward-html="selectedLesson.reward_html ?? null"
                :reward-delay-seconds="rewardDelaySeconds"
                :lesson-id="selectedLesson.id"
              />

              <!-- Promo Block -->
              <LessonPromoBlock
                v-if="selectedLesson.promo_delay_seconds !== null && selectedLesson.promo_delay_seconds !== undefined && selectedLesson.promo_html"
                :key="selectedLesson.id"
                :lesson-id="selectedLesson.id"
                :delay-seconds="selectedLesson.promo_delay_seconds"
                :promo-html="selectedLesson.promo_html"
              />

              <!-- HTML Content -->
              <div v-if="selectedLesson.html_content">
                <HtmlContent :content="selectedLesson.html_content" />
              </div>
            </template>

            <!-- No video: Content → Promo -->
            <template v-else>
              <!-- HTML Content -->
              <div v-if="selectedLesson.html_content">
                <HtmlContent :content="selectedLesson.html_content" />
              </div>

              <!-- Promo Block -->
              <LessonPromoBlock
                v-if="selectedLesson.promo_delay_seconds !== null && selectedLesson.promo_delay_seconds !== undefined && selectedLesson.promo_html"
                :key="selectedLesson.id"
                :lesson-id="selectedLesson.id"
                :delay-seconds="selectedLesson.promo_delay_seconds"
                :promo-html="selectedLesson.promo_html"
              />
            </template>

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
