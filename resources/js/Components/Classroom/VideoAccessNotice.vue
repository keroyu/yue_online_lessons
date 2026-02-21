<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  expired: { type: Boolean, required: true },
  remainingSeconds: { type: Number, default: null },
  targetCourses: { type: Array, default: () => [] },
  rewardHtml: { type: String, default: null },
  rewardDelaySeconds: { type: Number, default: null },
  lessonId: { type: Number, default: null },
})

// --- Free access countdown ---
const countdown = ref(props.remainingSeconds)
let accessTimer = null

// --- Reward block ---
const rewardKey = computed(() =>
  props.lessonId !== null ? `reward_earned_lesson_${props.lessonId}` : null
)

const showRewardColumn = computed(() =>
  props.rewardHtml !== null && props.rewardDelaySeconds !== null && rewardKey.value !== null
)

const rewardEarned = ref(false)
const rewardElapsed = ref(0)
let rewardTimer = null

onMounted(() => {
  // Free access countdown timer
  if (!props.expired && countdown.value > 0) {
    accessTimer = setInterval(() => {
      countdown.value--
      if (countdown.value <= 0) {
        clearInterval(accessTimer)
        window.location.reload()
      }
    }, 1000)
  }

  // Reward block
  if (!showRewardColumn.value) return

  if (localStorage.getItem(rewardKey.value) === 'true') {
    rewardEarned.value = true
    return
  }

  // Per-session timer: does NOT restore elapsed from localStorage on mount
  rewardTimer = setInterval(() => {
    rewardElapsed.value++
    if (rewardElapsed.value >= props.rewardDelaySeconds) {
      rewardEarned.value = true
      localStorage.setItem(rewardKey.value, 'true')
      clearInterval(rewardTimer)
    }
  }, 1000)
})

onUnmounted(() => {
  if (accessTimer) clearInterval(accessTimer)
  if (rewardTimer) clearInterval(rewardTimer)
  // Per-session: intentionally do NOT persist elapsed on unmount
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
  <!-- Within free viewing window: countdown (+ reward column if configured) -->
  <div v-if="!expired && formattedCountdown" class="mt-4">
    <div :class="showRewardColumn ? 'flex flex-col sm:flex-row gap-4' : ''">
      <!-- Left: countdown -->
      <div class="flex-1 bg-green-50 border border-green-200 rounded-lg p-4 text-center">
        <p class="text-sm text-green-700">課程免費公開中，剩餘</p>
        <p class="text-xl font-mono font-bold text-green-800">{{ formattedCountdown }}</p>
      </div>

      <!-- Right: reward column (only when showRewardColumn) -->
      <div
        v-if="showRewardColumn"
        class="flex-1 bg-yellow-50 border border-yellow-200 rounded-lg p-4"
      >
        <div v-if="rewardEarned" v-html="rewardHtml" />
        <p v-else class="text-yellow-800 font-medium text-sm text-center">你準時來上課了！真棒</p>
      </div>
    </div>
  </div>

  <!-- Expired: urgency promo block (+ reward/missed-reward notice) -->
  <div
    v-else-if="expired"
    class="mt-4 bg-amber-50 border border-amber-300 rounded-lg p-6"
  >
    <p class="text-amber-800 font-semibold mb-2">
      免費觀看期已結束，但我們為你保留了存取權。
    </p>

    <!-- Reward: earned → show reward HTML -->
    <div v-if="showRewardColumn && rewardEarned" v-html="rewardHtml" class="mb-4" />
    <!-- Reward: not earned → missed message -->
    <p
      v-else-if="showRewardColumn && !rewardEarned"
      class="text-amber-700 text-sm mb-4"
    >下次早點來喔，錯過了獎勵 :(</p>

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
