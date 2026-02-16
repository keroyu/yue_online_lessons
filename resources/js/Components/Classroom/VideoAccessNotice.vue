<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  expired: { type: Boolean, required: true },
  remainingSeconds: { type: Number, default: null },
  targetCourses: { type: Array, default: () => [] },
})

const countdown = ref(props.remainingSeconds)
let timer = null

onMounted(() => {
  if (!props.expired && countdown.value > 0) {
    timer = setInterval(() => {
      countdown.value--
      if (countdown.value <= 0) {
        clearInterval(timer)
        window.location.reload()
      }
    }, 1000)
  }
})

onUnmounted(() => {
  if (timer) clearInterval(timer)
})

const formattedCountdown = computed(() => {
  if (!countdown.value || countdown.value <= 0) return null
  const h = Math.floor(countdown.value / 3600)
  const m = Math.floor((countdown.value % 3600) / 60)
  const s = countdown.value % 60
  return `${h}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`
})
</script>

<template>
  <!-- Within free viewing window: countdown -->
  <div
    v-if="!expired && formattedCountdown"
    class="mt-4 bg-green-50 border border-green-200 rounded-lg p-4 text-center"
  >
    <p class="text-sm text-green-700">課程免費公開中，剩餘</p>
    <p class="text-xl font-mono font-bold text-green-800">{{ formattedCountdown }}</p>
  </div>

  <!-- Expired: urgency promo block -->
  <div
    v-else-if="expired"
    class="mt-4 bg-amber-50 border border-amber-300 rounded-lg p-6"
  >
    <p class="text-amber-800 font-semibold mb-2">
      免費觀看期已結束，但我們為你保留了存取權。
    </p>
    <p class="text-amber-700 mb-4">想要完整學習體驗？</p>
    <div v-if="targetCourses.length > 0" class="space-y-2">
      <a
        v-for="course in targetCourses"
        :key="course.id"
        :href="course.url"
        class="block w-full text-center bg-amber-500 hover:bg-amber-600 text-white font-semibold py-3 px-4 rounded-lg transition"
      >
        推薦購買：{{ course.name }}
      </a>
    </div>
    <a
      v-else
      href="/"
      class="block w-full text-center bg-amber-500 hover:bg-amber-600 text-white font-semibold py-3 px-4 rounded-lg transition"
    >
      探索更多課程
    </a>
  </div>
</template>
