<script setup>
import { ref, computed, watch, onMounted } from 'vue'

const props = defineProps({
  lesson: {
    type: Object,
    default: null,
  },
})

const emit = defineEmits(['save', 'close'])

const form = ref({
  title: '',
  video_url: '',
  html_content: '',
  duration_seconds: '',
})

const errors = ref({})

onMounted(() => {
  if (props.lesson) {
    form.value = {
      title: props.lesson.title || '',
      video_url: props.lesson.video_url || '',
      html_content: props.lesson.html_content || '',
      duration_seconds: props.lesson.duration_seconds || '',
    }
  }
})

const isEditing = computed(() => !!props.lesson)

const videoPlatform = computed(() => {
  const url = form.value.video_url
  if (!url) return null

  if (/vimeo\.com/.test(url)) return 'Vimeo'
  if (/youtube\.com|youtu\.be/.test(url)) return 'YouTube'
  return null
})

const validate = () => {
  errors.value = {}

  if (!form.value.title.trim()) {
    errors.value.title = '請輸入小節標題'
    return false
  }

  if (form.value.video_url && !videoPlatform.value) {
    errors.value.video_url = '請輸入有效的 Vimeo 或 YouTube 連結'
    return false
  }

  return true
}

const submit = () => {
  if (!validate()) return

  emit('save', {
    title: form.value.title,
    video_url: form.value.video_url || null,
    html_content: form.value.html_content || null,
    duration_seconds: form.value.duration_seconds ? parseInt(form.value.duration_seconds) : null,
  })
}

const close = () => {
  emit('close')
}
</script>

<template>
  <div class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <!-- Backdrop -->
      <div
        class="fixed inset-0 z-10 bg-gray-500 bg-opacity-75 transition-opacity"
        @click="close"
      />

      <!-- Modal -->
      <div class="relative z-20 inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
        <div>
          <h3 class="text-lg leading-6 font-medium text-gray-900">
            {{ isEditing ? '編輯小節' : '新增小節' }}
          </h3>

          <form @submit.prevent="submit" class="mt-6 space-y-6">
            <!-- Title -->
            <div>
              <label for="title" class="block text-sm font-medium text-gray-700">
                小節標題 <span class="text-red-500">*</span>
              </label>
              <input
                id="title"
                v-model="form.title"
                type="text"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                :class="{ 'border-red-300': errors.title }"
              />
              <p v-if="errors.title" class="mt-1 text-sm text-red-600">{{ errors.title }}</p>
            </div>

            <!-- Video URL -->
            <div>
              <label for="video_url" class="block text-sm font-medium text-gray-700">
                影片連結（Vimeo 或 YouTube）
              </label>
              <input
                id="video_url"
                v-model="form.video_url"
                type="url"
                placeholder="https://vimeo.com/... 或 https://youtube.com/..."
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                :class="{ 'border-red-300': errors.video_url }"
              />
              <p v-if="videoPlatform" class="mt-1 text-sm text-green-600">
                已偵測到 {{ videoPlatform }} 影片
              </p>
              <p v-if="errors.video_url" class="mt-1 text-sm text-red-600">{{ errors.video_url }}</p>
            </div>

            <!-- Duration -->
            <div>
              <label for="duration_seconds" class="block text-sm font-medium text-gray-700">
                時長（秒）
              </label>
              <input
                id="duration_seconds"
                v-model="form.duration_seconds"
                type="number"
                min="0"
                placeholder="例如: 230（顯示為 3:50）"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              />
            </div>

            <!-- HTML Content -->
            <div>
              <label for="html_content" class="block text-sm font-medium text-gray-700">
                HTML 內容（無影片時使用）
              </label>
              <textarea
                id="html_content"
                v-model="form.html_content"
                rows="6"
                placeholder="<h2>標題</h2>&#10;<p>內容...</p>"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm font-mono"
              />
              <p class="mt-1 text-sm text-gray-500">
                如無影片連結，將顯示此 HTML 內容
              </p>
            </div>

            <!-- Actions -->
            <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
              <button
                type="submit"
                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:col-start-2 sm:text-sm"
              >
                {{ isEditing ? '更新' : '新增' }}
              </button>
              <button
                type="button"
                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:col-start-1 sm:text-sm"
                @click="close"
              >
                取消
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>
