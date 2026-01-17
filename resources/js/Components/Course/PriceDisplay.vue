<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'

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

onMounted(() => {
  // Update every second for countdown effect
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
    <div v-if="isPromoActive" class="space-y-2">
      <div class="flex items-baseline gap-3 flex-wrap">
        <span class="text-lg text-gray-400 line-through">
          {{ formatPrice(originalPrice) }}
        </span>
        <span class="text-3xl font-bold text-red-600">
          {{ formatPrice(price) }}
        </span>
      </div>

      <!-- Countdown -->
      <div v-if="countdown" class="inline-flex items-center gap-2 bg-red-50 text-red-700 px-3 py-1.5 rounded-lg text-sm">
        <svg class="w-4 h-4 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span class="font-medium">
          優惠剩餘
          <span v-if="countdown.days > 0">{{ countdown.days }} 天 </span>
          <span class="tabular-nums">{{ String(countdown.hours).padStart(2, '0') }}:{{ String(countdown.minutes).padStart(2, '0') }}:{{ String(countdown.seconds).padStart(2, '0') }}</span>
        </span>
      </div>
    </div>

    <!-- No active promo: Just show the price -->
    <div v-else>
      <span class="text-3xl font-bold text-indigo-600">
        {{ formatPrice(displayPrice) }}
      </span>
    </div>
  </div>
</template>
