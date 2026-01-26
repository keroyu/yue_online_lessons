<script setup>
import { ref } from 'vue'
import LegalPolicyModal from '../Legal/LegalPolicyModal.vue'

const showModal = ref(false)
const modalType = ref('terms')

const openModal = (type) => {
  modalType.value = type
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
}
</script>

<template>
  <footer class="bg-gray-50 border-t border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="flex flex-col md:flex-row md:justify-between md:items-center">
        <div class="text-center md:text-left mb-4 md:mb-0">
          <p class="text-gray-600 text-sm">
            &copy; {{ new Date().getFullYear() }} 投好壯壯有限公司. All rights reserved.
          </p>
        </div>
        <div class="flex justify-center md:justify-end space-x-6">
          <button
            type="button"
            class="text-gray-400 hover:text-gray-600 text-sm transition-colors"
            @click="openModal('terms')"
          >
            服務條款
          </button>
          <button
            type="button"
            class="text-gray-400 hover:text-gray-600 text-sm transition-colors"
            @click="openModal('purchase')"
          >
            購買須知
          </button>
          <button
            type="button"
            class="text-gray-400 hover:text-gray-600 text-sm transition-colors"
            @click="openModal('privacy')"
          >
            隱私政策
          </button>
        </div>
      </div>
    </div>
  </footer>

  <LegalPolicyModal
    :show="showModal"
    :type="modalType"
    @close="closeModal"
  />
</template>
