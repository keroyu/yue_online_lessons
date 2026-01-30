<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'

const props = defineProps({
  price: {
    type: [Number, String],
    required: true,
  },
  originalPrice: {
    type: [Number, String],
    default: null,
  },
  promoEndsAt: {
    type: String,
    default: null,
  },
})

const now = ref(new Date())
let timer = null

// Animation state
const animatingSeconds = ref(false)
const animatingMinutes = ref(false)
const animatingHours = ref(false)
const animatingDays = ref(false)

onMounted(() => {
  timer = setInterval(() => {
    now.value = new Date()
  }, 1000)
})

onUnmounted(() => {
  if (timer) {
    clearInterval(timer)
  }
})

const isPromoActive = computed(() => {
  if (!props.originalPrice || !props.promoEndsAt) return false
  return new Date(props.promoEndsAt) > now.value
})

const countdown = computed(() => {
  if (!isPromoActive.value) return null

  const diff = new Date(props.promoEndsAt) - now.value
  if (diff <= 0) return null

  const days = Math.floor(diff / (1000 * 60 * 60 * 24))
  const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60))
  const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60))
  const seconds = Math.floor((diff % (1000 * 60)) / 1000)

  return { days, hours, minutes, seconds }
})

// Watch for countdown changes to trigger animations
watch(countdown, (newVal, oldVal) => {
  if (!newVal || !oldVal) return

  if (newVal.seconds !== oldVal.seconds) {
    animatingSeconds.value = true
    setTimeout(() => { animatingSeconds.value = false }, 300)
  }
  if (newVal.minutes !== oldVal.minutes) {
    animatingMinutes.value = true
    setTimeout(() => { animatingMinutes.value = false }, 300)
  }
  if (newVal.hours !== oldVal.hours) {
    animatingHours.value = true
    setTimeout(() => { animatingHours.value = false }, 300)
  }
  if (newVal.days !== oldVal.days) {
    animatingDays.value = true
    setTimeout(() => { animatingDays.value = false }, 300)
  }
}, { deep: true })

const displayPrice = computed(() => {
  if (isPromoActive.value) {
    return props.price
  }
  return props.originalPrice || props.price
})

const formatPrice = (price) => {
  return new Intl.NumberFormat('zh-TW', {
    style: 'currency',
    currency: 'TWD',
    minimumFractionDigits: 0,
  }).format(price)
}
</script>

<template>
  <div>
    <!-- Active Promo: Show original price (strikethrough) + promo price + countdown -->
    <div v-if="isPromoActive" class="space-y-3">
      <div class="flex items-baseline gap-3 flex-wrap">
        <span class="text-lg text-gray-400 line-through">
          {{ formatPrice(originalPrice) }}
        </span>
        <span class="text-3xl font-bold text-brand-red">
          {{ formatPrice(price) }}
        </span>
      </div>

      <!-- Countdown Timer (simplified) -->
      <div v-if="countdown" class="inline-block">
        <div class="text-brand-navy/70 text-xs mb-1.5 tracking-wide">優惠倒數</div>
        <div class="flex items-baseline gap-0.5">
          <!-- Days -->
          <span
            class="text-xl font-bold text-brand-orange tabular-nums"
            :class="{ 'animate-pulse-once': animatingDays }"
          >{{ countdown.days }}</span>
          <span class="text-brand-navy/60 text-sm mx-0.5">天</span>

          <!-- Hours -->
          <span
            class="text-xl font-bold text-brand-orange tabular-nums"
            :class="{ 'animate-pulse-once': animatingHours }"
          >{{ String(countdown.hours).padStart(2, '0') }}</span>
          <span class="text-brand-navy/60 text-sm mx-0.5">時</span>

          <!-- Minutes -->
          <span
            class="text-xl font-bold text-brand-orange tabular-nums"
            :class="{ 'animate-pulse-once': animatingMinutes }"
          >{{ String(countdown.minutes).padStart(2, '0') }}</span>
          <span class="text-brand-navy/60 text-sm mx-0.5">分</span>

          <!-- Seconds -->
          <span
            class="text-xl font-bold text-brand-orange tabular-nums"
            :class="{ 'animate-pulse-once': animatingSeconds }"
          >{{ String(countdown.seconds).padStart(2, '0') }}</span>
          <span class="text-brand-navy/60 text-sm ml-0.5">秒</span>
        </div>
      </div>
    </div>

    <!-- No active promo: Just show the price -->
    <div v-else>
      <span class="text-3xl font-bold text-brand-teal">
        {{ formatPrice(displayPrice) }}
      </span>
    </div>
  </div>
</template>

<style scoped>
/* Subtle pulse animation when digit changes */
.animate-pulse-once {
  animation: pulseOnce 0.3s ease-in-out;
}

@keyframes pulseOnce {
  0% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.1);
  }
  100% {
    transform: scale(1);
  }
}
</style>
