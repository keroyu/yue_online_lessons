<script setup>
import { watch, onMounted, onUnmounted } from 'vue'
import TermsContent from './TermsContent.vue'
import PurchaseContent from './PurchaseContent.vue'
import PrivacyContent from './PrivacyContent.vue'

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  type: {
    type: String,
    default: 'terms',
    validator: (value) => ['terms', 'purchase', 'privacy'].includes(value)
  }
})

const emit = defineEmits(['close'])

const titles = {
  terms: '服務條款',
  purchase: '購買須知',
  privacy: '隱私政策'
}

// ESC key handler
const handleKeydown = (e) => {
  if (e.key === 'Escape' && props.show) {
    emit('close')
  }
}

// Body scroll lock
watch(() => props.show, (newVal) => {
  if (newVal) {
    document.body.style.overflow = 'hidden'
  } else {
    document.body.style.overflow = ''
  }
})

onMounted(() => {
  document.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown)
  document.body.style.overflow = ''
})

const handleBackdropClick = (e) => {
  if (e.target === e.currentTarget) {
    emit('close')
  }
}
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition ease-in duration-150"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="show"
        class="fixed inset-0 z-50 overflow-y-auto"
        @click="handleBackdropClick"
      >
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black/50" aria-hidden="true" />

        <!-- Modal container -->
        <div class="flex min-h-full items-center justify-center p-4">
          <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
          >
            <div
              v-if="show"
              class="relative bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[80vh] flex flex-col"
              @click.stop
            >
              <!-- Header -->
              <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center rounded-t-lg">
                <h2 class="text-xl font-bold text-gray-900">
                  {{ titles[type] }}
                </h2>
                <button
                  type="button"
                  class="text-gray-400 hover:text-gray-600 transition-colors p-1 rounded-full hover:bg-gray-100"
                  @click="emit('close')"
                >
                  <span class="sr-only">關閉</span>
                  <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>

              <!-- Content -->
              <div class="flex-1 overflow-y-auto px-6 py-4">
                <TermsContent v-if="type === 'terms'" />
                <PurchaseContent v-if="type === 'purchase'" />
                <PrivacyContent v-if="type === 'privacy'" />
              </div>

              <!-- Footer -->
              <div class="border-t border-gray-200 px-6 py-4 bg-gray-50 rounded-b-lg">
                <button
                  type="button"
                  class="w-full sm:w-auto px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition-colors"
                  @click="emit('close')"
                >
                  我知道了
                </button>
              </div>
            </div>
          </Transition>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
