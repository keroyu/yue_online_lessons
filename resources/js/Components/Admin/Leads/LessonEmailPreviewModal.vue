<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  // { lesson_id, title } straight from the lesson stats row; null while closed.
  lesson: {
    type: Object,
    default: null,
  },
})

const emit = defineEmits(['close'])

const loading = ref(false)
const error = ref('')
const subject = ref('')
const html = ref('')

const reset = () => {
  subject.value = ''
  html.value = ''
  error.value = ''
}

const load = async (lessonId) => {
  loading.value = true
  reset()
  try {
    const res = await fetch(`/admin/drip/lessons/${lessonId}/email-preview`, {
      headers: { Accept: 'application/json' },
    })
    // A bounced session redirects to an HTML page, so status alone is not enough.
    if (!res.ok || !res.headers.get('content-type')?.includes('application/json')) {
      throw new Error()
    }
    const data = await res.json()
    subject.value = data.subject
    html.value = data.html
  } catch {
    error.value = '無法載入預覽，請重新整理後再試一次。'
  } finally {
    loading.value = false
  }
}

// Refetch per open: the letter changes whenever the lesson is edited, so a
// cached copy would quietly show the previous version.
watch(() => [props.show, props.lesson?.lesson_id], ([show, lessonId]) => {
  if (show && lessonId) {
    load(lessonId)
  } else if (!show) {
    reset()
  }
})

const handleKeydown = (e) => {
  if (e.key === 'Escape' && props.show) {
    emit('close')
  }
}

watch(() => props.show, (newVal) => {
  document.body.style.overflow = newVal ? 'hidden' : ''
})

onMounted(() => {
  document.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown)
  document.body.style.overflow = ''
})
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
        @click.self="emit('close')"
      >
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black/50 transition-opacity" aria-hidden="true" />

        <div class="flex min-h-full items-center justify-center p-4">
          <div
            class="relative bg-white rounded-xl shadow-2xl w-full max-w-2xl"
            @click.stop
          >
            <!-- Header -->
            <div class="px-6 py-4 border-b border-gray-200 flex items-start justify-between gap-4">
              <div class="min-w-0">
                <h2 class="text-lg font-semibold text-gray-900">信件預覽</h2>
                <p class="mt-0.5 text-xs text-gray-500 truncate">{{ lesson?.title }}</p>
              </div>
              <button
                type="button"
                class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100 transition-colors cursor-pointer"
                @click="emit('close')"
              >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <div class="px-6 py-5">
              <div v-if="loading" class="py-16 text-center text-sm text-gray-500">
                載入中…
              </div>

              <div v-else-if="error" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                {{ error }}
              </div>

              <template v-else>
                <!-- Subject line, assembled exactly as the sent mail does it -->
                <div class="mb-3">
                  <p class="text-xs font-medium text-gray-500">主旨</p>
                  <p class="mt-0.5 text-sm text-gray-900 break-words">{{ subject }}</p>
                </div>

                <!-- Sandboxed: the admin page's own CSS would flatter the letter,
                     and lesson content may contain raw HTML. -->
                <iframe
                  :srcdoc="html"
                  sandbox
                  title="信件內容預覽"
                  class="block w-full h-[28rem] rounded-lg border border-gray-300 bg-white"
                />

                <p class="mt-3 text-xs text-gray-500">
                  預覽使用範例資料：稱呼為「小明」、停止接收連結不可點、不含開信追蹤像素。實際寄出時會帶入該訂閱者的資料。
                </p>
              </template>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
