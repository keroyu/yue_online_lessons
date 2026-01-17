<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import PriceDisplay from '@/Components/Course/PriceDisplay.vue'
import LegalPolicyModal from '@/Components/Legal/LegalPolicyModal.vue'

const props = defineProps({
  course: {
    type: Object,
    required: true,
  },
})

const agreed = ref(false)

const getTypeLabel = (type) => {
  const labels = {
    lecture: '講座',
    mini: '迷你課',
    full: '完整課程',
  }
  return labels[type] || type
}

// Generate Portaly URL from product_id
const portalyUrl = computed(() => {
  if (!props.course.portaly_product_id) {
    return null
  }
  return `https://portaly.cc/kyontw/product/${props.course.portaly_product_id}`
})

const openPortaly = () => {
  if (portalyUrl.value && agreed.value) {
    window.open(portalyUrl.value, '_blank')
  }
}

// Legal Policy Modal
const showLegalModal = ref(false)
const legalModalType = ref('terms')

const openLegalModal = (type) => {
  legalModalType.value = type
  showLegalModal.value = true
}

const closeLegalModal = () => {
  showLegalModal.value = false
}
</script>

<template>
  <Head :title="course.name" />

  <div class="py-8 sm:py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Back link -->
      <Link href="/" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 mb-6">
        <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
        返回課程列表
      </Link>

      <div class="bg-white rounded-lg shadow-sm overflow-hidden">
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
            <svg class="w-16 h-16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
            </svg>
          </div>

          <!-- Type badge -->
          <span class="absolute top-4 left-4 bg-indigo-600 text-white text-sm px-3 py-1 rounded">
            {{ getTypeLabel(course.type) }}
          </span>
        </div>

        <!-- Content -->
        <div class="p-6 sm:p-8">
          <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">
            {{ course.name }}
          </h1>

          <p class="text-lg text-gray-600 mt-2">
            {{ course.tagline }}
          </p>

          <div class="flex items-center mt-4 text-gray-500">
            <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            {{ course.instructor_name }}
          </div>

          <!-- Course Info -->
          <div v-if="course.duration_formatted" class="flex items-center mt-2 text-gray-500">
            <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ course.duration_formatted }}
          </div>

          <!-- Status Badge -->
          <div v-if="course.status === 'preorder'" class="mt-4">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
              預購中
            </span>
          </div>

          <!-- Description -->
          <div class="mt-8">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">課程介紹</h2>
            <!-- HTML content (if available) -->
            <div
              v-if="course.description_html"
              class="course-content"
              v-html="course.description_html"
            />
            <!-- Plain text fallback -->
            <div v-else class="course-content">
              <p class="whitespace-pre-line">{{ course.description }}</p>
            </div>
          </div>

          <!-- Purchase section -->
          <div class="mt-8 pt-8 border-t border-gray-100">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-6">
              <div>
                <PriceDisplay
                  :price="course.price"
                  :original-price="course.original_price"
                  :promo-ends-at="course.promo_ends_at"
                />
              </div>

              <div class="flex flex-col gap-3">
                <!-- Consent checkbox -->
                <label class="flex items-start gap-2 cursor-pointer">
                  <input
                    type="checkbox"
                    v-model="agreed"
                    class="mt-1 h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                  />
                  <span class="text-sm text-gray-600">
                    我已閱讀並同意
                    <button
                      type="button"
                      class="text-indigo-600 hover:underline"
                      @click.prevent="openLegalModal('terms')"
                    >服務條款</button>
                    和
                    <button
                      type="button"
                      class="text-indigo-600 hover:underline"
                      @click.prevent="openLegalModal('purchase')"
                    >購買須知</button>
                  </span>
                </label>

                <button
                  @click="openPortaly"
                  :disabled="!agreed || !portalyUrl"
                  :class="[
                    'w-full sm:w-auto px-8 py-3 rounded-lg font-semibold text-white transition-colors',
                    agreed && portalyUrl
                      ? 'bg-indigo-600 hover:bg-indigo-700'
                      : 'bg-gray-300 cursor-not-allowed'
                  ]"
                >
                  立即購買
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Legal Policy Modal -->
  <LegalPolicyModal
    :show="showLegalModal"
    :type="legalModalType"
    @close="closeLegalModal"
  />
</template>
