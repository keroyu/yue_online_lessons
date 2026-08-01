<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  courseId: { type: Number, required: true },
  delaySeconds: { type: Number, default: null },
  promoHtml: { type: String, default: null },
})

const UNLOCK_KEY = computed(() => `promo_unlocked_course_${props.courseId}`)
const isUnlocked = ref(false)
const elapsedSeconds = ref(0)
let timer = null

onMounted(() => {
  if (localStorage.getItem(UNLOCK_KEY.value) === 'true') {
    isUnlocked.value = true
    return
  }

  if (props.delaySeconds === 0) {
    unlock()
    return
  }

  timer = setInterval(() => {
    elapsedSeconds.value++
    if (elapsedSeconds.value >= props.delaySeconds) unlock()
  }, 1000)
})

onUnmounted(() => {
  if (timer) clearInterval(timer)
})

const unlock = () => {
  isUnlocked.value = true
  localStorage.setItem(UNLOCK_KEY.value, 'true')
  if (timer) {
    clearInterval(timer)
    timer = null
  }
}

const remainingSeconds = computed(() => Math.max(0, props.delaySeconds - elapsedSeconds.value))

const formattedTime = computed(() => {
  const m = Math.floor(remainingSeconds.value / 60)
  const s = remainingSeconds.value % 60
  return `${m}:${s.toString().padStart(2, '0')}`
})
</script>

<template>
  <!-- Same white band and column width as the course description section, so the
       promo reads as a continuation of it rather than a separate strip -->
  <div class="bg-white px-4 sm:px-6 pb-10 overflow-x-hidden">
    <div class="max-w-4xl mx-auto">
      <!-- course-content gives the admin-authored HTML the same heading, list and
           image styling as the course description (Tailwind preflight otherwise
           strips h2/h3 back to body text) -->
      <div v-if="isUnlocked && promoHtml" class="course-content" v-html="promoHtml" />
      <div v-else-if="!isUnlocked" class="bg-gray-50 rounded-xl border border-dashed border-gray-300 px-6 py-7 text-center">
        <p class="text-gray-600 mb-2">還有一個東西要給你，稍等一下…</p>
        <p class="text-2xl font-mono text-gray-800">{{ formattedTime }}</p>
      </div>
    </div>
  </div>
</template>
