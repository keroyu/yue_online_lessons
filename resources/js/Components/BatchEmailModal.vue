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
  }
})

const emit = defineEmits(['close', 'sent'])

// Form state
const subject = ref('')
const body = ref('')
const errors = ref({})
const sending = ref(false)
const fetchingIds = ref(false)

// Character counts
const subjectMaxLength = 200
const bodyMaxLength = 10000

const subjectLength = computed(() => subject.value.length)
const bodyLength = computed(() => body.value.length)

// Reset form when modal opens
watch(() => props.show, (newVal) => {
  if (newVal) {
    subject.value = ''
    body.value = ''
    errors.value = {}
    sending.value = false
  }
})

// Validate form
const validate = () => {
  errors.value = {}

  if (!subject.value.trim()) {
    errors.value.subject = '郵件主旨為必填'
  } else if (subject.value.length > subjectMaxLength) {
    errors.value.subject = `郵件主旨不能超過 ${subjectMaxLength} 字`
  }

  if (!body.value.trim()) {
    errors.value.body = '郵件內容為必填'
  } else if (body.value.length > bodyMaxLength) {
    errors.value.body = `郵件內容不能超過 ${bodyMaxLength} 字`
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

// Send batch email
const sendEmail = async () => {
  if (!validate()) return

  sending.value = true
  errors.value = {}

  try {
    // Get member IDs to send to
    let idsToSend = props.memberIds

    // If selectAllMatching, we need to fetch all matching IDs
    if (props.selectAllMatching) {
      idsToSend = await fetchAllMatchingMemberIds()
      if (idsToSend.length === 0) {
        errors.value.general = '無法取得會員清單，請重試'
        sending.value = false
        return
      }
    }

    const response = await axios.post('/admin/members/batch-email', {
      member_ids: idsToSend,
      subject: subject.value,
      body: body.value,
    })

    emit('sent', {
      success: true,
      message: response.data.message,
      queuedCount: response.data.queued_count,
      skippedCount: response.data.skipped_count,
    })

    emit('close')
  } catch (err) {
    if (err.response?.status === 422) {
      errors.value = err.response.data.errors || {}
      if (err.response.data.message) {
        errors.value.general = err.response.data.message
      }
    } else {
      errors.value.general = '發送失敗，請稍後再試'
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

// Consistent styling classes (matching CourseForm/LessonForm)
const inputClasses = 'mt-2 block w-full rounded-lg border-gray-300 px-4 py-3 text-base shadow-sm transition-colors focus:border-indigo-500 focus:ring-indigo-500'
const inputErrorClasses = 'border-red-300 focus:border-red-500 focus:ring-red-500'
const labelClasses = 'block text-sm font-semibold text-gray-900'
const helpTextClasses = 'mt-2 text-sm text-gray-500'
const errorTextClasses = 'mt-2 text-sm text-red-600'
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
              class="relative bg-white rounded-xl shadow-2xl max-w-2xl w-full flex flex-col transform transition-all"
              @click.stop
            >
              <!-- Header -->
              <div class="px-6 py-5 border-b border-gray-200">
                <div class="flex justify-between items-start">
                  <div>
                    <h2 class="text-xl font-semibold text-gray-900">
                      發送批次郵件
                    </h2>
                    <p class="mt-1 text-sm text-gray-500">
                      編輯郵件內容並發送給選取的會員
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
                  <div class="bg-indigo-50 border border-indigo-100 rounded-lg p-4">
                    <div class="flex items-center gap-3">
                      <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                      </div>
                      <p class="text-sm text-indigo-800">
                        將發送郵件給 <strong class="font-semibold">{{ selectedCount }}</strong> 位會員
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

                  <!-- Subject field -->
                  <div>
                    <label for="subject" :class="labelClasses">
                      郵件主旨 <span class="text-red-500">*</span>
                    </label>
                    <input
                      id="subject"
                      v-model="subject"
                      type="text"
                      placeholder="請輸入郵件主旨"
                      :disabled="sending"
                      :class="[inputClasses, errors.subject ? inputErrorClasses : '']"
                    />
                    <div class="mt-2 flex justify-between items-center">
                      <span v-if="errors.subject" class="text-sm text-red-600">
                        {{ errors.subject }}
                      </span>
                      <span v-else></span>
                      <span class="text-sm" :class="subjectLength > subjectMaxLength ? 'text-red-600' : 'text-gray-500'">
                        {{ subjectLength }} / {{ subjectMaxLength }}
                      </span>
                    </div>
                  </div>

                  <!-- Body field -->
                  <div>
                    <label for="body" :class="labelClasses">
                      郵件內容 <span class="text-red-500">*</span>
                    </label>
                    <textarea
                      id="body"
                      v-model="body"
                      rows="10"
                      placeholder="請輸入郵件內容..."
                      :disabled="sending"
                      class="mt-2 block w-full rounded-lg border-gray-300 px-4 py-3 text-base shadow-sm transition-colors focus:border-indigo-500 focus:ring-indigo-500 leading-relaxed"
                      :class="{ 'border-red-300 focus:border-red-500 focus:ring-red-500': errors.body }"
                    ></textarea>
                    <div class="mt-2 flex justify-between items-center">
                      <span v-if="errors.body" class="text-sm text-red-600">
                        {{ errors.body }}
                      </span>
                      <span v-else :class="helpTextClasses">支援純文字格式</span>
                      <span class="text-sm" :class="bodyLength > bodyMaxLength ? 'text-red-600' : 'text-gray-500'">
                        {{ bodyLength }} / {{ bodyMaxLength }}
                      </span>
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
                  class="px-6 py-2.5 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-lg shadow-sm hover:bg-indigo-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center gap-2"
                  @click="sendEmail"
                  :disabled="sending || fetchingIds"
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
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    發送郵件
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
