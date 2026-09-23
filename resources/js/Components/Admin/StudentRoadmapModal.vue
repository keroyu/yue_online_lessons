<script setup>
import { ref, watch } from 'vue'
import RoadmapBoard from '@/Components/Classroom/RoadmapBoard.vue'

const props = defineProps({
  open: {
    type: Boolean,
    default: false,
  },
  courseId: {
    type: [Number, String],
    default: null,
  },
  userId: {
    type: [Number, String],
    default: null,
  },
  userName: {
    type: String,
    default: '',
  },
})

const emit = defineEmits(['close'])

const loading = ref(false)
const error = ref(null)
const board = ref(null)

const load = async () => {
  if (!props.courseId || !props.userId) return

  loading.value = true
  error.value = null
  board.value = null

  try {
    const response = await fetch(`/admin/homework/roadmap/${props.courseId}/${props.userId}`, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    })

    if (!response.ok) throw new Error(`HTTP ${response.status}`)

    const data = await response.json()
    board.value = data.board
  } catch (e) {
    error.value = '讀取失敗，請重新整理後再試'
  } finally {
    loading.value = false
  }
}

watch(() => props.open, (isOpen) => {
  if (isOpen) load()
})
</script>

<template>
  <Teleport to="body">
    <div
      v-if="open"
      class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/40 p-4 sm:p-8"
      @click.self="emit('close')"
    >
      <div class="w-full max-w-3xl rounded-xl bg-brand-cream/40 shadow-xl">
        <div class="flex items-center justify-between border-b border-gray-200 bg-white px-5 py-3 rounded-t-xl">
          <div class="min-w-0">
            <h3 class="text-base font-semibold text-brand-navy truncate">
              {{ userName || '學員' }} 的 Roadmap
            </h3>
            <p class="text-xs text-gray-500">唯讀檢視，不會影響學員的勾選</p>
          </div>
          <button
            type="button"
            class="text-gray-400 hover:text-gray-700 cursor-pointer"
            @click="emit('close')"
          >
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="p-5">
          <p v-if="loading" class="py-8 text-center text-sm text-gray-500">讀取中…</p>
          <p v-else-if="error" class="py-8 text-center text-sm text-red-600">{{ error }}</p>
          <p v-else-if="!board" class="py-8 text-center text-sm text-gray-500">這門課沒有 Roadmap。</p>

          <RoadmapBoard
            v-else
            :board="board"
            :course-id="courseId"
            readonly
          />
        </div>
      </div>
    </div>
  </Teleport>
</template>
