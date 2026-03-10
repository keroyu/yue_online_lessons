<script setup>
import { router } from '@inertiajs/vue3'
import { ref, watch, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  transaction: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['close'])

const submitting = ref(false)

const confirmRefund = () => {
  submitting.value = true
  router.patch(route('admin.transactions.refund', props.transaction.id), {}, {
    onSuccess: () => {
      submitting.value = false
      emit('close')
    },
    onError: () => {
      submitting.value = false
      emit('close')
    },
  })
}

// ESC key handler
const handleKeydown = (e) => {
  if (e.key === 'Escape' && props.show) {
    emit('close')
  }
}

// Body scroll lock
watch(() => props.show, (newVal) => {
  document.body.style.overflow = newVal ? 'hidden' : ''
})

onMounted(() => {
  document.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown)
  document.body.style.overflow = ''
})
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
        @click.self="emit('close')"
      >
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black/50 transition-opacity" aria-hidden="true" />

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
              class="relative bg-white rounded-xl shadow-2xl max-w-md w-full"
              @click.stop
            >
              <!-- Header -->
              <div class="px-6 py-5 border-b border-gray-200 flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-900">確認退款</h2>
                <button
                  type="button"
                  class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100 transition-colors"
                  @click="emit('close')"
                >
                  <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>

              <!-- Content -->
              <div class="px-6 py-6">
                <!-- Warning -->
                <div class="bg-red-50 border border-red-100 rounded-lg p-4">
                  <div class="flex gap-3">
                    <div class="flex-shrink-0">
                      <svg class="h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                      </svg>
                    </div>
                    <div class="text-sm text-red-800">
                      <p class="font-semibold">確定要將此交易標記為退款？</p>
                      <p class="mt-1">此操作將撤銷該會員對課程的存取權，且無法復原。</p>
                    </div>
                  </div>
                </div>

                <!-- Transaction summary -->
                <div class="mt-4 space-y-2 text-sm text-gray-700">
                  <div class="flex justify-between">
                    <span class="text-gray-500">交易 ID</span>
                    <span>#{{ transaction.id }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-gray-500">課程</span>
                    <span>{{ transaction.course?.name || '-' }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-gray-500">購買者</span>
                    <span>{{ transaction.buyer_email || transaction.user?.email || '-' }}</span>
                  </div>
                </div>
              </div>

              <!-- Footer -->
              <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3">
                <button
                  type="button"
                  class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg shadow-sm hover:bg-gray-50 transition-colors"
                  @click="emit('close')"
                  :disabled="submitting"
                >
                  取消
                </button>
                <button
                  type="button"
                  class="px-6 py-2.5 text-sm font-medium text-white bg-red-600 border border-transparent rounded-lg shadow-sm hover:bg-red-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center gap-2"
                  @click="confirmRefund"
                  :disabled="submitting"
                >
                  <template v-if="submitting">
                    <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    處理中...
                  </template>
                  <template v-else>
                    確認退款
                  </template>
                </button>
              </div>
            </div>
          </Transition>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
