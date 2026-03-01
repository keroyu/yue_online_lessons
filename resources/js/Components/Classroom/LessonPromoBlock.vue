<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  lessonId: { type: Number, required: true },
  delaySeconds: { type: Number, required: true },
  promoHtml: { type: String, default: null },
  promoUrl: { type: String, default: null },
})

const UNLOCK_KEY = computed(() => `promo_unlocked_lesson_${props.lessonId}`)
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
    if (elapsedSeconds.value >= props.delaySeconds) {
      unlock()
    }
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
  <div class="my-6 border-t pt-6">
    <div v-if="isUnlocked">
      <div v-if="promoHtml" class="px-4 py-6" v-html="promoHtml" />
      <div v-if="promoUrl" class="py-4 text-center">
        <a :href="promoUrl" class="promo-btn">立即瞭解</a>
      </div>
    </div>
    <div v-else class="bg-gray-100 rounded-lg p-6 text-center">
      <p class="text-gray-600 mb-2">解鎖進階資訊，請先完成學習</p>
      <p class="text-2xl font-mono text-gray-800">{{ formattedTime }}</p>
    </div>
  </div>
</template>

<style scoped>
.promo-btn {
  display: inline-block;
  background: #F0C14B;
  color: #373557;
  padding: 12px 40px;
  border-radius: 9999px;
  border: 1px solid rgba(199, 163, 59, 0.5);
  text-decoration: none;
  font-weight: 600;
  font-size: 15px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  transition: background 0.2s, box-shadow 0.2s, transform 0.15s;
}

.promo-btn:hover {
  background: #e8b33a;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  transform: translateY(-2px);
}
</style>
