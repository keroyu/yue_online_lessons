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
  isAdmin: {
    type: Boolean,
    default: false,
  },
  isPreviewMode: {
    type: Boolean,
    default: false,
  },
})

const agreed = ref(false)
const showPreviewAlert = ref(false)

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
  // If in preview mode (draft course), show alert instead of redirecting
  if (props.isPreviewMode) {
    showPreviewAlert.value = true
    return
  }

  if (portalyUrl.value && agreed.value) {
    window.open(portalyUrl.value, '_blank')
  }
}

const closePreviewAlert = () => {
  showPreviewAlert.value = false
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

  <!-- Preview Mode Banner -->
  <div
    v-if="isPreviewMode"
    class="bg-blue-600 text-white text-center py-3 px-4"
  >
    <div class="max-w-4xl mx-auto flex items-center justify-center gap-2">
      <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
      </svg>
      <span class="font-medium">預覽模式 - 此課程尚未上架，僅管理員可見</span>
    </div>
  </div>

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
          <span class="absolute top-4 left-4 bg-brand-teal text-white text-sm px-3 py-1 rounded">
            {{ getTypeLabel(course.type) }}
          </span>

          <!-- Draft badge (preview mode) -->
          <span
            v-if="isPreviewMode"
            class="absolute top-4 right-4 bg-gray-500 text-white text-sm px-3 py-1 rounded font-medium"
          >
            草稿
          </span>
        </div>

        <!-- Content -->
        <div class="p-6 sm:p-8">
          <h1 class="text-2xl sm:text-3xl font-bold text-brand-navy">
            {{ course.name }}
          </h1>

          <p class="text-lg text-gray-600 mt-2">
            {{ course.tagline }}
          </p>

          <!-- Instructor, Duration & Price Row -->
          <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mt-4">
            <!-- Left: Instructor & Duration -->
            <div class="space-y-2">
              <div class="flex items-center text-gray-500">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                {{ course.instructor_name }}
              </div>

              <div v-if="course.duration_formatted" class="flex items-center text-gray-500">
                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ course.duration_formatted }}
              </div>
            </div>

            <!-- Right: Price Block (Header) -->
            <div class="bg-gradient-to-br from-brand-cream to-white border border-brand-teal/20 rounded-xl px-5 py-4 sm:min-w-[180px]">
              <PriceDisplay
                :price="course.price"
                :original-price="course.original_price"
                :promo-ends-at="course.promo_ends_at"
              />
            </div>
          </div>

          <!-- Status Badge -->
          <div v-if="course.status === 'preorder'" class="mt-4">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
              預購中
            </span>
          </div>

          <!-- Description -->
          <div class="mt-8">
            <h2 class="text-lg font-semibold text-brand-navy mb-4">課程介紹</h2>
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
              <!-- Left: Price Block -->
              <div class="bg-gradient-to-br from-brand-cream to-white border border-brand-teal/20 rounded-xl px-5 py-4">
                <PriceDisplay
                  :price="course.price"
                  :original-price="course.original_price"
                  :promo-ends-at="course.promo_ends_at"
                />
              </div>

              <!-- Right: Consent & Purchase Button -->
              <div class="flex flex-col gap-3 sm:items-end">
                <!-- Preview mode notice -->
                <div v-if="isPreviewMode" class="text-sm text-gray-500 bg-gray-50 px-3 py-2 rounded">
                  草稿課程，僅供預覽
                </div>

                <!-- Consent checkbox -->
                <label v-if="!isPreviewMode" class="flex items-start gap-2 cursor-pointer">
                  <input
                    type="checkbox"
                    v-model="agreed"
                    class="mt-1 h-4 w-4 text-brand-teal border-gray-300 rounded focus:ring-brand-teal"
                  />
                  <span class="text-sm text-gray-600">
                    我已閱讀並同意
                    <button
                      type="button"
                      class="text-brand-teal hover:underline"
                      @click.prevent="openLegalModal('terms')"
                    >服務條款</button>
                    和
                    <button
                      type="button"
                      class="text-brand-teal hover:underline"
                      @click.prevent="openLegalModal('purchase')"
                    >購買須知</button>
                  </span>
                </label>

                <button
                  @click="openPortaly"
                  :disabled="!isPreviewMode && (!agreed || !portalyUrl)"
                  :class="[
                    'w-full sm:w-auto px-10 py-3 rounded-full font-semibold transition-all shadow-sm',
                    isPreviewMode
                      ? 'bg-gray-300 text-gray-500 cursor-not-allowed'
                      : (agreed && portalyUrl
                          ? 'bg-brand-gold hover:bg-brand-gold-dark text-brand-navy border border-brand-gold-dark/50 hover:shadow-md active:scale-[0.98] cursor-pointer'
                          : 'bg-gray-200 text-gray-400 cursor-not-allowed border border-gray-300')
                  ]"
                >
                  {{ isPreviewMode ? '預覽購買按鈕' : '立即購買' }}
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

  <!-- Preview Alert Modal -->
  <Teleport to="body">
    <div
      v-if="showPreviewAlert"
      class="fixed inset-0 z-50 flex items-center justify-center p-4"
    >
      <!-- Backdrop -->
      <div
        class="absolute inset-0 bg-black/50"
        @click="closePreviewAlert"
      />

      <!-- Modal -->
      <div class="relative bg-white rounded-lg shadow-xl max-w-sm w-full p-6">
        <div class="text-center">
          <div class="mx-auto w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mb-4">
            <svg class="w-6 h-6 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <h3 class="text-lg font-semibold text-gray-900 mb-2">
            草稿課程
          </h3>
          <p class="text-gray-600 mb-6">
            此為草稿課程，僅供預覽。正式上架後才能進行購買。
          </p>
          <button
            @click="closePreviewAlert"
            class="w-full px-4 py-2 bg-brand-teal text-white rounded-lg hover:bg-brand-teal/80 transition-colors font-medium"
          >
            我知道了
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
