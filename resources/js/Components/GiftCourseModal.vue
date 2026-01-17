<script setup>
import { ref, watch, onMounted, onUnmounted, computed } from 'vue'

// Use the globally configured axios with CSRF token
const axios = window.axios

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  selectedCount: {
    type: Number,
    default: 0
  },
  memberIds: {
    type: Array,
    default: () => []
  },
  selectAllMatching: {
    type: Boolean,
    default: false
  },
  // Filters for fetching all matching member IDs
  filters: {
    type: Object,
    default: () => ({})
  },
  // Available courses for selection
  courses: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['close', 'gifted'])

// Form state
const selectedCourseId = ref('')
const errors = ref({})
const sending = ref(false)
const fetchingIds = ref(false)

// Get the selected course object
const selectedCourse = computed(() => {
  if (!selectedCourseId.value) return null
  return props.courses.find(c => c.id === parseInt(selectedCourseId.value))
})

// Reset form when modal opens
watch(() => props.show, (newVal) => {
  if (newVal) {
    selectedCourseId.value = ''
    errors.value = {}
    sending.value = false
  }
})

// Validate form
const validate = () => {
  errors.value = {}

  if (!selectedCourseId.value) {
    errors.value.course_id = '請選擇要贈送的課程'
  }

  return Object.keys(errors.value).length === 0
}

// Fetch all matching member IDs when selectAllMatching is true
const fetchAllMatchingMemberIds = async () => {
  fetchingIds.value = true
  try {
    const response = await axios.get('/admin/members', {
      params: {
        search: props.filters.search || undefined,
        course_id: props.filters.course_id || undefined,
        per_page: 10000, // Get all matching members
      },
      headers: {
        'Accept': 'application/json',
        'X-Inertia': false,
      }
    })
    return response.data.members?.data?.map(m => m.id) || []
  } catch (err) {
    console.error('Failed to fetch member IDs:', err)
    return []
  } finally {
    fetchingIds.value = false
  }
}

// Gift course to members
const giftCourse = async () => {
  if (!validate()) return

  sending.value = true
  errors.value = {}

  try {
    // Get member IDs to gift to
    let idsToGift = props.memberIds

    // If selectAllMatching, we need to fetch all matching IDs
    if (props.selectAllMatching) {
      idsToGift = await fetchAllMatchingMemberIds()
      if (idsToGift.length === 0) {
        errors.value.general = '無法取得會員清單，請重試'
        sending.value = false
        return
      }
    }

    const response = await axios.post('/admin/members/gift-course', {
      member_ids: idsToGift,
      course_id: parseInt(selectedCourseId.value),
    })

    emit('gifted', {
      success: response.data.success,
      message: response.data.message,
      giftedCount: response.data.gifted_count,
      alreadyOwnedCount: response.data.already_owned_count,
      emailQueuedCount: response.data.email_queued_count,
      skippedNoEmailCount: response.data.skipped_no_email_count,
    })

    emit('close')
  } catch (err) {
    if (err.response?.status === 422) {
      errors.value = err.response.data.errors || {}
      if (err.response.data.message) {
        errors.value.general = err.response.data.message
      }
    } else if (err.response?.status === 429) {
      errors.value.general = '操作太頻繁，請稍後再試'
    } else {
      errors.value.general = '贈送失敗，請稍後再試'
    }
  } finally {
    sending.value = false
  }
}

// ESC key handler
const handleKeydown = (e) => {
  if (e.key === 'Escape' && props.show) {
    emit('close')
  }
}

// Body scroll lock
watch(() => props.show, (newVal) => {
  if (newVal) {
    document.body.style.overflow = 'hidden'
  } else {
    document.body.style.overflow = ''
  }
})

onMounted(() => {
  document.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown)
  document.body.style.overflow = ''
})

const handleBackdropClick = (e) => {
  if (e.target === e.currentTarget) {
    emit('close')
  }
}

// Consistent styling classes
const labelClasses = 'block text-sm font-semibold text-gray-900'
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition ease-in duration-150"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="show"
        class="fixed inset-0 z-50 overflow-y-auto"
        @click="handleBackdropClick"
      >
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black/50 transition-opacity" aria-hidden="true" />

        <!-- Modal container -->
        <div class="flex min-h-full items-center justify-center p-4">
          <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
          >
            <div
              v-if="show"
              class="relative bg-white rounded-xl shadow-2xl max-w-lg w-full flex flex-col transform transition-all"
              @click.stop
            >
              <!-- Header -->
              <div class="px-6 py-5 border-b border-gray-200">
                <div class="flex justify-between items-start">
                  <div>
                    <h2 class="text-xl font-semibold text-gray-900">
                      贈送課程
                    </h2>
                    <p class="mt-1 text-sm text-gray-500">
                      選擇要贈送的課程給選取的會員
                    </p>
                  </div>
                  <button
                    type="button"
                    class="text-gray-400 hover:text-gray-600 transition-colors p-1.5 rounded-lg hover:bg-gray-100"
                    @click="emit('close')"
                  >
                    <span class="sr-only">關閉</span>
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </div>
              </div>

              <!-- Content -->
              <div class="px-6 py-6">
                <div class="space-y-6">
                  <!-- Recipient info -->
                  <div class="bg-green-50 border border-green-100 rounded-lg p-4">
                    <div class="flex items-center gap-3">
                      <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
                        </svg>
                      </div>
                      <p class="text-sm text-green-800">
                        將贈送課程給 <strong class="font-semibold">{{ selectedCount }}</strong> 位會員
                      </p>
                    </div>
                  </div>

                  <!-- General error -->
                  <div v-if="errors.general" class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <div class="flex items-center gap-3">
                      <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                      </div>
                      <p class="text-sm text-red-600">{{ errors.general }}</p>
                    </div>
                  </div>

                  <!-- Course selection -->
                  <div>
                    <label for="course" :class="labelClasses">
                      選擇課程 <span class="text-red-500">*</span>
                    </label>
                    <select
                      id="course"
                      v-model="selectedCourseId"
                      :disabled="sending"
                      class="mt-2 block w-full rounded-lg border-gray-300 px-4 py-3 text-base shadow-sm transition-colors focus:border-green-500 focus:ring-green-500"
                      :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': errors.course_id }"
                    >
                      <option value="">請選擇課程</option>
                      <option v-for="course in courses" :key="course.id" :value="course.id">
                        {{ course.name }}
                      </option>
                    </select>
                    <p v-if="errors.course_id" class="mt-2 text-sm text-red-600">
                      {{ errors.course_id }}
                    </p>
                  </div>

                  <!-- Course preview -->
                  <div v-if="selectedCourse" class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <h4 class="font-medium text-gray-900 mb-2">{{ selectedCourse.name }}</h4>
                    <p class="text-sm text-gray-600">
                      {{ selectedCourse.description || '（無課程簡介）' }}
                    </p>
                  </div>

                  <!-- Info note -->
                  <div class="bg-yellow-50 border border-yellow-100 rounded-lg p-4">
                    <div class="flex gap-3">
                      <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                      </div>
                      <div class="text-sm text-yellow-800">
                        <p>贈送後，會員將收到通知信並可立即開始學習課程。</p>
                        <p class="mt-1">已擁有此課程的會員將被略過。</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Footer -->
              <div class="mt-2 flex items-center justify-end gap-3 px-6 py-5 border-t border-gray-200">
                <button
                  type="button"
                  class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 transition-colors"
                  @click="emit('close')"
                  :disabled="sending"
                >
                  取消
                </button>
                <button
                  type="button"
                  class="px-6 py-2.5 text-sm font-medium text-white bg-green-600 border border-transparent rounded-lg shadow-sm hover:bg-green-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center gap-2"
                  @click="giftCourse"
                  :disabled="sending || fetchingIds || !selectedCourseId"
                >
                  <template v-if="sending || fetchingIds">
                    <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    處理中...
                  </template>
                  <template v-else>
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
                    </svg>
                    確認贈送
                  </template>
                </button>
              </div>
            </div>
          </Transition>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
