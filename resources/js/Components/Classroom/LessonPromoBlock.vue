<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  lessonId: { type: Number, required: true },
  delaySeconds: { type: Number, required: true },
  promoHtml: { type: String, required: true },
})

const UNLOCK_KEY = computed(() => `promo_unlocked_lesson_${props.lessonId}`)
const ELAPSED_KEY = computed(() => `promo_elapsed_lesson_${props.lessonId}`)
const isUnlocked = ref(false)
const elapsedSeconds = ref(0)
let timer = null

onMounted(() => {
  // Check if already unlocked
  if (localStorage.getItem(UNLOCK_KEY.value) === 'true') {
    isUnlocked.value = true
    return
  }

  if (props.delaySeconds === 0) {
    unlock()
    return
  }

  // Restore elapsed time from previous session
  const savedElapsed = parseInt(localStorage.getItem(ELAPSED_KEY.value) || '0', 10)
  elapsedSeconds.value = savedElapsed

  if (savedElapsed >= props.delaySeconds) {
    unlock()
    return
  }

  // Start timer, persist elapsed time every 5 seconds
  timer = setInterval(() => {
    elapsedSeconds.value++
    if (elapsedSeconds.value % 5 === 0) {
      localStorage.setItem(ELAPSED_KEY.value, String(elapsedSeconds.value))
    }
    if (elapsedSeconds.value >= props.delaySeconds) {
      unlock()
    }
  }, 1000)
})

onUnmounted(() => {
  if (timer) clearInterval(timer)
  // Persist elapsed time on unmount
  if (!isUnlocked.value) {
    localStorage.setItem(ELAPSED_KEY.value, String(elapsedSeconds.value))
  }
})

const unlock = () => {
  isUnlocked.value = true
  localStorage.setItem(UNLOCK_KEY.value, 'true')
  localStorage.removeItem(ELAPSED_KEY.value)
  if (timer) {
    clearInterval(timer)
    timer = null
  }
}

const remainingSeconds = computed(() =>
  Math.max(0, props.delaySeconds - elapsedSeconds.value)
)

const formattedTime = computed(() => {
  const m = Math.floor(remainingSeconds.value / 60)
  const s = remainingSeconds.value % 60
  return `${m}:${s.toString().padStart(2, '0')}`
})
</script>

<template>
  <div class="mt-6 border-t pt-6">
    <div v-if="isUnlocked" v-html="promoHtml" />
    <div v-else class="bg-gray-100 rounded-lg p-6 text-center">
      <p class="text-gray-600 mb-2">請先觀看課程</p>
      <p class="text-2xl font-mono text-gray-800">{{ formattedTime }}</p>
    </div>
  </div>
</template>
