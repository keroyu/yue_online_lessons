<script setup>
import { ref, watch, onMounted, onUnmounted, computed } from 'vue'
import axios from 'axios'

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
        <div class="fixed inset-0 bg-black/50" aria-hidden="true" />

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
              class="relative bg-white rounded-lg shadow-xl max-w-2xl w-full flex flex-col"
              @click.stop
            >
              <!-- Header -->
              <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center rounded-t-lg">
                <h2 class="text-xl font-bold text-gray-900">
                  發送批次郵件
                </h2>
                <button
                  type="button"
                  class="text-gray-400 hover:text-gray-600 transition-colors p-1 rounded-full hover:bg-gray-100"
                  @click="emit('close')"
                >
                  <span class="sr-only">關閉</span>
                  <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>

              <!-- Content -->
              <div class="px-6 py-4 space-y-4">
                <!-- Recipient info -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                  <p class="text-sm text-blue-800">
                    將發送郵件給 <strong>{{ selectedCount }}</strong> 位會員
                  </p>
                </div>

                <!-- General error -->
                <div v-if="errors.general" class="bg-red-50 border border-red-200 rounded-lg p-3">
                  <p class="text-sm text-red-600">{{ errors.general }}</p>
                </div>

                <!-- Subject field -->
                <div>
                  <label for="subject" class="block text-sm font-medium text-gray-700 mb-1">
                    郵件主旨 <span class="text-red-500">*</span>
                  </label>
                  <input
                    id="subject"
                    v-model="subject"
                    type="text"
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    :class="{ 'border-red-500': errors.subject }"
                    placeholder="請輸入郵件主旨"
                    :disabled="sending"
                  />
                  <div class="mt-1 flex justify-between">
                    <span v-if="errors.subject" class="text-xs text-red-600">
                      {{ errors.subject }}
                    </span>
                    <span v-else></span>
                    <span class="text-xs" :class="subjectLength > subjectMaxLength ? 'text-red-600' : 'text-gray-500'">
                      {{ subjectLength }} / {{ subjectMaxLength }}
                    </span>
                  </div>
                </div>

                <!-- Body field -->
                <div>
                  <label for="body" class="block text-sm font-medium text-gray-700 mb-1">
                    郵件內容 <span class="text-red-500">*</span>
                  </label>
                  <textarea
                    id="body"
                    v-model="body"
                    rows="10"
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    :class="{ 'border-red-500': errors.body }"
                    placeholder="請輸入郵件內容..."
                    :disabled="sending"
                  ></textarea>
                  <div class="mt-1 flex justify-between">
                    <span v-if="errors.body" class="text-xs text-red-600">
                      {{ errors.body }}
                    </span>
                    <span v-else></span>
                    <span class="text-xs" :class="bodyLength > bodyMaxLength ? 'text-red-600' : 'text-gray-500'">
                      {{ bodyLength }} / {{ bodyMaxLength }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Footer -->
              <div class="border-t border-gray-200 px-6 py-4 bg-gray-50 rounded-b-lg flex justify-end gap-3">
                <button
                  type="button"
                  class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors"
                  @click="emit('close')"
                  :disabled="sending"
                >
                  取消
                </button>
                <button
                  type="button"
                  class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                  @click="sendEmail"
                  :disabled="sending || fetchingIds"
                >
                  <span v-if="sending || fetchingIds" class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    處理中...
                  </span>
                  <span v-else>發送郵件</span>
                </button>
              </div>
            </div>
          </Transition>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
