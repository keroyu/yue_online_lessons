<script setup>
import { ref, watch, onMounted, onUnmounted, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  memberId: {
    type: Number,
    default: null
  }
})

const emit = defineEmits(['close'])

// Member data state
const loading = ref(false)
const member = ref(null)
const courses = ref([])
const error = ref(null)

// Edit mode state
const editMode = ref(false)
const editForm = ref({
  nickname: '',
  birth_date: ''
})
const editErrors = ref({})
const saving = ref(false)

// Fetch member details when modal opens
watch(() => [props.show, props.memberId], async ([show, memberId]) => {
  if (show && memberId) {
    await fetchMemberDetails()
  } else {
    // Reset state when modal closes
    member.value = null
    courses.value = []
    error.value = null
    editMode.value = false
    editErrors.value = {}
  }
}, { immediate: true })

const fetchMemberDetails = async () => {
  loading.value = true
  error.value = null
  try {
    const response = await axios.get(`/admin/members/${props.memberId}`)
    member.value = response.data.member
    courses.value = response.data.courses
    // Initialize edit form with current values
    editForm.value = {
      nickname: member.value.nickname || '',
      birth_date: member.value.birth_date || ''
    }
  } catch (err) {
    if (err.response?.status === 404) {
      error.value = '找不到該會員，可能已被刪除'
      // Close modal and redirect
      setTimeout(() => {
        emit('close')
        router.visit('/admin/members', {
          preserveScroll: true
        })
      }, 2000)
    } else {
      error.value = '載入會員資料失敗，請稍後再試'
    }
  } finally {
    loading.value = false
  }
}

// Start editing
const startEdit = () => {
  editMode.value = true
  editErrors.value = {}
}

// Cancel editing
const cancelEdit = () => {
  editMode.value = false
  editErrors.value = {}
  // Reset form to original values
  editForm.value = {
    nickname: member.value?.nickname || '',
    birth_date: member.value?.birth_date || ''
  }
}

// Save changes
const saveEdit = async () => {
  saving.value = true
  editErrors.value = {}

  try {
    await axios.patch(`/admin/members/${props.memberId}`, {
      nickname: editForm.value.nickname || null,
      birth_date: editForm.value.birth_date || null
    })

    // Update local member data
    member.value.nickname = editForm.value.nickname || null
    member.value.birth_date = editForm.value.birth_date || null

    editMode.value = false

    // Refresh the page data to update the list
    router.reload({ only: ['members'] })
  } catch (err) {
    if (err.response?.status === 422) {
      editErrors.value = err.response.data.errors || {}
    } else {
      editErrors.value = { general: '儲存失敗，請稍後再試' }
    }
  } finally {
    saving.value = false
  }
}

// Format date for display
const formatDate = (dateString) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('zh-TW')
}

const formatDateTime = (dateString) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleString('zh-TW')
}

// Get progress bar color based on percentage
const getProgressColor = (percent) => {
  if (percent >= 80) return 'bg-green-500'
  if (percent >= 50) return 'bg-blue-500'
  if (percent >= 20) return 'bg-yellow-500'
  return 'bg-gray-400'
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
              class="relative bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[85vh] flex flex-col"
              @click.stop
            >
              <!-- Header -->
              <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center rounded-t-lg">
                <h2 class="text-xl font-bold text-gray-900">
                  會員詳情
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
              <div class="flex-1 overflow-y-auto px-6 py-4">
                <!-- Loading state -->
                <div v-if="loading" class="flex items-center justify-center py-12">
                  <svg class="animate-spin h-8 w-8 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  <span class="ml-2 text-gray-600">載入中...</span>
                </div>

                <!-- Error state -->
                <div v-else-if="error" class="py-12 text-center">
                  <svg class="mx-auto h-12 w-12 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                  </svg>
                  <p class="mt-2 text-gray-600">{{ error }}</p>
                </div>

                <!-- Member details -->
                <div v-else-if="member" class="space-y-6">
                  <!-- Basic Info Section -->
                  <div>
                    <div class="flex items-center justify-between mb-4">
                      <h3 class="text-lg font-semibold text-gray-900">基本資料</h3>
                      <button
                        v-if="!editMode"
                        type="button"
                        class="text-sm text-indigo-600 hover:text-indigo-800"
                        @click="startEdit"
                      >
                        編輯
                      </button>
                    </div>

                    <!-- General error message -->
                    <div v-if="editErrors.general" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-md">
                      <p class="text-sm text-red-600">{{ editErrors.general }}</p>
                    </div>

                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                      <div>
                        <dt class="text-sm font-medium text-gray-500">Email</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ member.email }}</dd>
                      </div>
                      <div>
                        <dt class="text-sm font-medium text-gray-500">姓名</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ member.real_name || '-' }}</dd>
                      </div>
                      <div>
                        <dt class="text-sm font-medium text-gray-500">電話</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ member.phone || '-' }}</dd>
                      </div>

                      <!-- Nickname - editable in modal -->
                      <div>
                        <dt class="text-sm font-medium text-gray-500">暱稱</dt>
                        <dd class="mt-1">
                          <template v-if="editMode">
                            <input
                              v-model="editForm.nickname"
                              type="text"
                              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                              :class="{ 'border-red-500': editErrors.nickname }"
                              placeholder="輸入暱稱"
                            />
                            <p v-if="editErrors.nickname" class="mt-1 text-xs text-red-600">
                              {{ editErrors.nickname[0] }}
                            </p>
                          </template>
                          <span v-else class="text-sm text-gray-900">{{ member.nickname || '-' }}</span>
                        </dd>
                      </div>

                      <!-- Birthday - editable in modal -->
                      <div>
                        <dt class="text-sm font-medium text-gray-500">生日</dt>
                        <dd class="mt-1">
                          <template v-if="editMode">
                            <input
                              v-model="editForm.birth_date"
                              type="date"
                              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                              :class="{ 'border-red-500': editErrors.birth_date }"
                            />
                            <p v-if="editErrors.birth_date" class="mt-1 text-xs text-red-600">
                              {{ editErrors.birth_date[0] }}
                            </p>
                          </template>
                          <span v-else class="text-sm text-gray-900">{{ formatDate(member.birth_date) }}</span>
                        </dd>
                      </div>

                      <div>
                        <dt class="text-sm font-medium text-gray-500">註冊時間</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ formatDateTime(member.created_at) }}</dd>
                      </div>
                      <div>
                        <dt class="text-sm font-medium text-gray-500">最後登入</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ formatDateTime(member.last_login_at) }}</dd>
                      </div>
                      <div>
                        <dt class="text-sm font-medium text-gray-500">最後登入 IP</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ member.last_login_ip || '-' }}</dd>
                      </div>
                    </dl>

                    <!-- Edit mode buttons -->
                    <div v-if="editMode" class="mt-4 flex gap-2 justify-end">
                      <button
                        type="button"
                        class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                        @click="cancelEdit"
                        :disabled="saving"
                      >
                        取消
                      </button>
                      <button
                        type="button"
                        class="px-3 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed"
                        @click="saveEdit"
                        :disabled="saving"
                      >
                        <span v-if="saving">儲存中...</span>
                        <span v-else>儲存</span>
                      </button>
                    </div>
                  </div>

                  <!-- Courses Section -->
                  <div class="border-t border-gray-200 pt-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">擁有課程</h3>

                    <!-- Empty state -->
                    <div v-if="courses.length === 0" class="text-center py-8 bg-gray-50 rounded-lg">
                      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                      </svg>
                      <p class="mt-2 text-sm text-gray-600">尚未購買任何課程</p>
                    </div>

                    <!-- Course list with progress -->
                    <div v-else class="space-y-4">
                      <div
                        v-for="course in courses"
                        :key="course.id"
                        class="bg-gray-50 rounded-lg p-4"
                      >
                        <div class="flex justify-between items-start mb-2">
                          <div>
                            <h4 class="font-medium text-gray-900">{{ course.name }}</h4>
                            <p class="text-sm text-gray-500">
                              購買於 {{ formatDate(course.purchased_at) }}
                            </p>
                          </div>
                          <span class="text-sm font-medium" :class="course.progress_percent === 100 ? 'text-green-600' : 'text-gray-600'">
                            {{ course.progress_percent }}%
                          </span>
                        </div>

                        <!-- Progress bar -->
                        <div class="mt-2">
                          <div class="flex justify-between text-xs text-gray-500 mb-1">
                            <span>進度：{{ course.completed_lessons }} / {{ course.total_lessons }} 課</span>
                            <span v-if="course.progress_percent === 100" class="text-green-600">已完成</span>
                          </div>
                          <div class="w-full bg-gray-200 rounded-full h-2">
                            <div
                              class="h-2 rounded-full transition-all duration-300"
                              :class="getProgressColor(course.progress_percent)"
                              :style="{ width: `${course.progress_percent}%` }"
                            ></div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Footer -->
              <div class="border-t border-gray-200 px-6 py-4 bg-gray-50 rounded-b-lg">
                <button
                  type="button"
                  class="w-full sm:w-auto px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors"
                  @click="emit('close')"
                >
                  關閉
                </button>
              </div>
            </div>
          </Transition>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
