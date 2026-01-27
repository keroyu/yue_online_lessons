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
      class: 'bg-gray-500 text-white',
    }
  }

  if (props.course.status === 'preorder') {
    return {
      label: '預購中',
      class: 'bg-yellow-500 text-white',
    }
  }

  if (props.course.status === 'selling') {
    return {
      label: '熱賣中',
      class: 'bg-green-500 text-white',
    }
  }

  return null
})
</script>

<template>
  <Link
    :href="`/course/${course.id}`"
    class="block bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow overflow-hidden"
  >
    <!-- Thumbnail -->
    <div class="aspect-[3/2] bg-gray-100 relative">
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
      <span class="absolute top-2 left-2 bg-indigo-600 text-white text-xs px-2 py-1 rounded">
        {{ getTypeLabel(course.type) }}
      </span>

      <!-- Status badge (admin only) -->
      <span
        v-if="statusBadge"
        class="absolute top-2 right-2 text-xs px-2 py-1 rounded font-medium"
        :class="statusBadge.class"
      >
        {{ statusBadge.label }}
      </span>
    </div>

    <!-- Content -->
    <div class="p-4">
      <h3 class="font-semibold text-gray-900 line-clamp-1">
        {{ course.name }}
      </h3>
      <p class="text-sm text-gray-500 mt-1 line-clamp-2">
        {{ course.tagline }}
      </p>
      <div class="flex items-center justify-between mt-3">
        <span class="text-sm text-gray-400">
          {{ course.instructor_name }}
        </span>
        <span class="font-semibold text-indigo-600">
          {{ formatPrice(course.price) }}
        </span>
      </div>
    </div>
  </Link>
</template>
