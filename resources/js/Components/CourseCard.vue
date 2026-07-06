<script setup>
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
  course: {
    type: Object,
    required: true,
  },
  showStatusBadge: {
    type: Boolean,
    default: false,
  },
})

const formatPrice = (price) => {
  return new Intl.NumberFormat('zh-TW', {
    style: 'currency',
    currency: 'TWD',
    minimumFractionDigits: 0,
  }).format(price)
}

const getTypeLabel = (type) => {
  const labels = {
    lecture: '講座',
    mini: '迷你課',
    full: '完整課程',
  }
  return labels[type] || type
}

// Status badge configuration
const statusBadge = computed(() => {
  if (!props.showStatusBadge) return null

  const isDraft = props.course.status === 'draft' || !props.course.is_published

  if (isDraft) {
    return {
      label: '草稿',
      class: 'bg-gray-600 text-white',
    }
  }

  if (props.course.status === 'preorder') {
    return {
      label: '預購中',
      class: 'bg-brand-gold text-brand-navy',
    }
  }

  if (props.course.status === 'selling') {
    return {
      label: '熱賣中',
      class: 'bg-brand-red text-white',
    }
  }

  return null
})

// Hidden badge (admin only)
const isHidden = computed(() => {
  return props.showStatusBadge && props.course.is_visible === false
})
</script>

<template>
  <Link
    :href="`/course/${course.id}`"
    class="block bg-white border border-gray-200 hover:border-brand-teal transition-colors overflow-hidden"
  >
    <!-- Thumbnail -->
    <div class="aspect-video bg-gray-100 relative">
      <img
        v-if="course.thumbnail"
        :src="course.thumbnail"
        :alt="course.name"
        class="w-full h-full object-cover"
      />
      <div
        v-else
        class="w-full h-full flex items-center justify-center text-gray-400"
      >
        <svg class="w-12 h-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
        </svg>
      </div>

      <!-- Type badge -->
      <span class="absolute top-2 left-2 bg-brand-navy/90 text-white text-sm sm:text-xs px-2 py-1 rounded-none font-medium tracking-wide">
        {{ getTypeLabel(course.product_type) }}
      </span>

      <!-- Status badge (admin only) -->
      <span
        v-if="statusBadge"
        class="absolute top-2 right-2 text-sm sm:text-xs px-2 py-1 rounded-none font-medium tracking-wide"
        :class="statusBadge.class"
      >
        {{ statusBadge.label }}
      </span>

      <!-- Hidden badge (admin only) -->
      <span
        v-if="isHidden"
        class="absolute top-10 right-2 text-sm sm:text-xs px-2 py-1 rounded-none font-medium tracking-wide bg-gray-800 text-white"
        title="此課程已隱藏，不會顯示於首頁"
      >
        隱藏
      </span>
    </div>

    <!-- Content — text scaled up on mobile, back to desktop size at sm: -->
    <div class="p-4">
      <h3 class="flex items-start gap-2 text-lg sm:text-base font-semibold text-brand-navy">
        <span class="mt-1.5 sm:mt-1 w-1 h-4 shrink-0 bg-brand-teal"></span>
        <span class="line-clamp-1">{{ course.name }}</span>
      </h3>
      <p class="text-base sm:text-sm text-gray-500 mt-1 line-clamp-2">
        {{ course.tagline }}
      </p>
      <div class="flex items-center justify-between mt-3">
        <span class="text-base sm:text-sm text-gray-400">
          {{ course.instructor_name }}
        </span>
        <!-- Promo pricing: show original (strikethrough) + promo price (red, larger) -->
        <div v-if="course.is_promo_active" class="text-right">
          <span class="text-base sm:text-sm text-gray-400 line-through">
            {{ formatPrice(course.original_price) }}
          </span>
          <span class="ml-1 text-2xl sm:text-lg font-bold text-brand-red">
            {{ formatPrice(course.price) }}
          </span>
        </div>
        <!-- Regular pricing -->
        <span v-else class="text-2xl sm:text-base font-bold sm:font-semibold text-brand-teal">
          {{ formatPrice(course.price) }}
        </span>
      </div>
    </div>
  </Link>
</template>
