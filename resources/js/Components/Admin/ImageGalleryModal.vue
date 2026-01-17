<script setup>
import { ref, computed, watch } from 'vue'
import { router, useForm } from '@inertiajs/vue3'

const props = defineProps({
  courseId: {
    type: Number,
    required: true,
  },
  images: {
    type: Array,
    default: () => [],
  },
  show: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['close', 'insert'])

const selectedImage = ref(null)
const customWidth = ref('')
const customHeight = ref('')
const uploading = ref(false)
const fileInput = ref(null)

const uploadForm = useForm({
  image: null,
})

// Reset selection when modal opens/closes
watch(() => props.show, (newVal) => {
  if (!newVal) {
    selectedImage.value = null
    customWidth.value = ''
    customHeight.value = ''
  }
})

const selectImage = (image) => {
  selectedImage.value = image
  customWidth.value = ''
  customHeight.value = ''
}

const calculateHeight = () => {
  if (customWidth.value && selectedImage.value?.width && selectedImage.value?.height) {
    const ratio = selectedImage.value.height / selectedImage.value.width
    customHeight.value = Math.round(customWidth.value * ratio)
  }
}

const calculateWidth = () => {
  if (customHeight.value && selectedImage.value?.width && selectedImage.value?.height) {
    const ratio = selectedImage.value.width / selectedImage.value.height
    customWidth.value = Math.round(customHeight.value * ratio)
  }
}

const insertImage = () => {
  if (!selectedImage.value) return

  const img = selectedImage.value
  let html = `<img src="${img.url}" alt="${img.filename}"`
  if (customWidth.value) html += ` width="${customWidth.value}"`
  if (customHeight.value) html += ` height="${customHeight.value}"`
  html += ' />'

  emit('insert', html)
  emit('close')
}

const handleFileChange = (event) => {
  const file = event.target.files[0]
  if (!file) return

  uploading.value = true
  uploadForm.image = file

  uploadForm.post(`/admin/courses/${props.courseId}/images`, {
    preserveScroll: true,
    onSuccess: () => {
      uploading.value = false
      uploadForm.reset()
      if (fileInput.value) {
        fileInput.value.value = ''
      }
    },
    onError: () => {
      uploading.value = false
    },
  })
}

const deleteImage = (image) => {
  if (!confirm(`確定要刪除圖片「${image.filename}」嗎？`)) return

  router.delete(`/admin/images/${image.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      if (selectedImage.value?.id === image.id) {
        selectedImage.value = null
      }
    },
  })
}

const close = () => {
  emit('close')
}
</script>

<template>
  <Teleport to="body">
    <div
      v-if="show"
      class="fixed inset-0 z-50 overflow-y-auto"
      aria-labelledby="modal-title"
      role="dialog"
      aria-modal="true"
    >
      <!-- Backdrop -->
      <div
        class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
        @click="close"
      ></div>

      <!-- Modal -->
      <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
        <div
          class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-4xl"
          @click.stop
        >
          <!-- Header -->
          <div class="border-b border-gray-200 px-6 py-4 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900">選擇圖片</h3>
            <button
              type="button"
              class="text-gray-400 hover:text-gray-500"
              @click="close"
            >
              <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Content -->
          <div class="px-6 py-4 max-h-[60vh] overflow-y-auto">
            <!-- Upload button -->
            <div class="mb-4">
              <label class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 cursor-pointer transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                </svg>
                {{ uploading ? '上傳中...' : '上傳圖片' }}
                <input
                  ref="fileInput"
                  type="file"
                  class="sr-only"
                  accept="image/jpeg,image/png,image/gif,image/webp"
                  :disabled="uploading"
                  @change="handleFileChange"
                />
              </label>
              <p v-if="uploadForm.errors.image" class="mt-2 text-sm text-red-600">{{ uploadForm.errors.image }}</p>
            </div>

            <!-- Image Grid -->
            <div v-if="images.length > 0" class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3">
              <div
                v-for="image in images"
                :key="image.id"
                class="group relative aspect-square bg-gray-100 rounded-lg overflow-hidden cursor-pointer ring-2 transition-all"
                :class="selectedImage?.id === image.id ? 'ring-indigo-500' : 'ring-transparent hover:ring-gray-300'"
                @click="selectImage(image)"
              >
                <img
                  :src="image.url"
                  :alt="image.filename"
                  class="w-full h-full object-cover"
                />

                <!-- Delete button -->
                <button
                  type="button"
                  class="absolute top-1 right-1 p-1 rounded-full bg-red-600 text-white opacity-0 group-hover:opacity-100 transition-opacity"
                  title="刪除"
                  @click.stop="deleteImage(image)"
                >
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>

                <!-- Selected checkmark -->
                <div
                  v-if="selectedImage?.id === image.id"
                  class="absolute bottom-1 right-1 p-1 rounded-full bg-indigo-500 text-white"
                >
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                  </svg>
                </div>
              </div>
            </div>

            <div v-else class="text-center py-12 bg-gray-50 rounded-lg">
              <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              <p class="mt-4 text-gray-500">尚無圖片，請先上傳</p>
            </div>

            <!-- Dimension Settings -->
            <div v-if="selectedImage" class="mt-6 p-4 bg-gray-50 rounded-lg">
              <h4 class="text-sm font-medium text-gray-900 mb-3">尺寸設定（選填）</h4>
              <div class="flex items-center gap-4">
                <div class="flex-1">
                  <label class="block text-sm text-gray-600 mb-1">寬度 (px)</label>
                  <input
                    v-model="customWidth"
                    type="number"
                    min="1"
                    placeholder="自動"
                    class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    @input="calculateHeight"
                  />
                </div>
                <div class="flex-shrink-0 pt-6 text-gray-400">
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                  </svg>
                </div>
                <div class="flex-1">
                  <label class="block text-sm text-gray-600 mb-1">高度 (px)</label>
                  <input
                    v-model="customHeight"
                    type="number"
                    min="1"
                    placeholder="自動"
                    class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    @input="calculateWidth"
                  />
                </div>
              </div>
              <p class="mt-2 text-xs text-gray-500">
                原始尺寸: {{ selectedImage.width || '未知' }} x {{ selectedImage.height || '未知' }} px
                <span class="mx-2">|</span>
                僅填一項會自動等比計算
              </p>
            </div>
          </div>

          <!-- Footer -->
          <div class="border-t border-gray-200 px-6 py-4 flex items-center justify-end gap-3">
            <button
              type="button"
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
              @click="close"
            >
              取消
            </button>
            <button
              type="button"
              class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              :disabled="!selectedImage"
              @click="insertImage"
            >
              插入圖片
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>
