<script setup>
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'

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
  markdownMode: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['close', 'insert'])

// Insert selection — ordered array, preserves click sequence
const selectedForInsert = ref([])
const customWidth = ref('')
const customHeight = ref('')
const uploading = ref(false)
const uploadError = ref(null)
const fileInput = ref(null)
const selectedForDelete = ref(new Set())

// Single selected image — used for aspect ratio calculation and original dimensions display
const primaryImage = computed(() =>
  selectedForInsert.value.length === 1 ? selectedForInsert.value[0] : null
)

// Returns 1-based insertion order for an image, 0 if not selected
const insertOrder = (image) =>
  selectedForInsert.value.findIndex(i => i.id === image.id) + 1

// Reset all selections when modal closes
watch(() => props.show, (newVal) => {
  if (!newVal) {
    selectedForInsert.value = []
    selectedForDelete.value = new Set()
    customWidth.value = ''
    customHeight.value = ''
  }
})

const toggleInsertSelection = (image) => {
  const idx = selectedForInsert.value.findIndex(i => i.id === image.id)
  if (idx >= 0) {
    selectedForInsert.value = selectedForInsert.value.filter((_, i) => i !== idx)
  } else {
    selectedForInsert.value = [...selectedForInsert.value, image]
  }
  // Clear dimensions when switching from single to multi (avoid applying wrong ratio)
  if (selectedForInsert.value.length !== 1) {
    customWidth.value = ''
    customHeight.value = ''
  }
}

const calculateHeight = () => {
  if (customWidth.value && primaryImage.value?.width && primaryImage.value?.height) {
    const ratio = primaryImage.value.height / primaryImage.value.width
    customHeight.value = Math.round(customWidth.value * ratio)
  }
}

const calculateWidth = () => {
  if (customHeight.value && primaryImage.value?.width && primaryImage.value?.height) {
    const ratio = primaryImage.value.width / primaryImage.value.height
    customWidth.value = Math.round(customHeight.value * ratio)
  }
}

const insertImage = () => {
  if (!selectedForInsert.value.length) return

  const texts = selectedForInsert.value.map(img => {
    if (props.markdownMode) {
      if (!customWidth.value && !customHeight.value) {
        return `![${img.filename}](${img.url})`
      }
      let t = `<img src="${img.url}" alt="${img.filename}"`
      if (customWidth.value) t += ` width="${customWidth.value}"`
      if (customHeight.value) t += ` height="${customHeight.value}"`
      return t + '>'
    } else {
      let t = `<img src="${img.url}" alt="${img.filename}"`
      if (customWidth.value) t += ` width="${customWidth.value}"`
      if (customHeight.value) t += ` height="${customHeight.value}"`
      return t + ' />'
    }
  })

  emit('insert', texts.join('\n\n'))
  emit('close')
}

const handleFileChange = (event) => {
  const files = Array.from(event.target.files)
  if (!files.length) return

  uploading.value = true
  uploadError.value = null

  const data = new FormData()
  files.forEach(f => data.append('images[]', f))

  router.post(`/admin/courses/${props.courseId}/images/batch`, data, {
    preserveScroll: true,
    onSuccess: () => {
      uploading.value = false
      if (fileInput.value) fileInput.value.value = ''
    },
    onError: (errors) => {
      uploading.value = false
      uploadError.value = errors.images
        || Object.values(errors)[0]
        || '上傳失敗，請重試'
    },
  })
}

// Batch delete selection
const toggleDeleteSelection = (event, image) => {
  event.stopPropagation()
  const next = new Set(selectedForDelete.value)
  next.has(image.id) ? next.delete(image.id) : next.add(image.id)
  selectedForDelete.value = next
}

const isAllSelected = computed(() =>
  props.images.length > 0 && selectedForDelete.value.size === props.images.length
)

const toggleSelectAll = () => {
  if (isAllSelected.value) {
    selectedForDelete.value = new Set()
  } else {
    selectedForDelete.value = new Set(props.images.map(i => i.id))
  }
}

const batchDelete = () => {
  if (!selectedForDelete.value.size) return
  const count = selectedForDelete.value.size
  if (!confirm(`確定要刪除 ${count} 張圖片嗎？`)) return

  const deletingIds = new Set(selectedForDelete.value)

  router.delete('/admin/images/batch', {
    data: { ids: [...deletingIds] },
    preserveScroll: true,
    onSuccess: () => {
      selectedForInsert.value = selectedForInsert.value.filter(img => !deletingIds.has(img.id))
      selectedForDelete.value = new Set()
    },
  })
}

const deleteImage = (image) => {
  if (!confirm(`確定要刪除圖片「${image.filename}」嗎？`)) return

  router.delete(`/admin/images/${image.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      selectedForInsert.value = selectedForInsert.value.filter(img => img.id !== image.id)
      const next = new Set(selectedForDelete.value)
      next.delete(image.id)
      selectedForDelete.value = next
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
                  multiple
                  :disabled="uploading"
                  @change="handleFileChange"
                />
              </label>
              <p v-if="uploadError" class="mt-2 text-sm text-red-600">{{ uploadError }}</p>
            </div>

            <!-- Batch delete toolbar -->
            <div v-if="images.length > 0" class="flex items-center gap-3 mb-3">
              <button
                type="button"
                class="text-sm text-indigo-600 hover:text-indigo-800 transition-colors"
                @click="toggleSelectAll"
              >
                {{ isAllSelected ? '取消全選' : '全選' }}
              </button>
              <span v-if="selectedForDelete.size > 0" class="text-sm text-gray-500">
                已選 {{ selectedForDelete.size }} 張
              </span>
              <button
                v-if="selectedForDelete.size > 0"
                type="button"
                class="ml-auto inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors"
                @click="batchDelete"
              >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                刪除已選 ({{ selectedForDelete.size }})
              </button>
            </div>

            <!-- Image Grid -->
            <div v-if="images.length > 0" class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3">
              <div
                v-for="image in images"
                :key="image.id"
                class="group relative aspect-square bg-gray-100 rounded-lg overflow-hidden cursor-pointer ring-2 transition-all"
                :class="insertOrder(image) > 0 ? 'ring-indigo-500' : 'ring-transparent hover:ring-gray-300'"
                @click="toggleInsertSelection(image)"
              >
                <img
                  :src="image.url"
                  :alt="image.filename"
                  class="w-full h-full object-cover"
                />

                <!-- Batch delete checkbox (top-left) -->
                <div
                  class="absolute top-1 left-1 transition-opacity"
                  :class="selectedForDelete.has(image.id) ? 'opacity-100' : 'opacity-0 group-hover:opacity-100'"
                  @click.stop="toggleDeleteSelection($event, image)"
                >
                  <div
                    class="w-5 h-5 rounded border-2 flex items-center justify-center transition-colors"
                    :class="selectedForDelete.has(image.id)
                      ? 'bg-red-500 border-red-500'
                      : 'bg-white border-gray-300 hover:border-red-400'"
                  >
                    <svg
                      v-if="selectedForDelete.has(image.id)"
                      class="w-3 h-3 text-white"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke="currentColor"
                    >
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                    </svg>
                  </div>
                </div>

                <!-- Single delete button (top-right) -->
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

                <!-- Insert order badge (bottom-right) -->
                <div
                  v-if="insertOrder(image) > 0"
                  class="absolute bottom-1 right-1 w-5 h-5 rounded-full bg-indigo-500 text-white text-xs flex items-center justify-center font-bold"
                >
                  {{ insertOrder(image) }}
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
            <div v-if="selectedForInsert.length > 0" class="mt-6 p-4 bg-gray-50 rounded-lg">
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
                <template v-if="primaryImage">
                  原始尺寸: {{ primaryImage.width || '未知' }} x {{ primaryImage.height || '未知' }} px
                  <span class="mx-2">|</span>
                  僅填一項會自動等比計算
                </template>
                <template v-else>
                  已選 {{ selectedForInsert.length }} 張，尺寸將統一套用至所有圖片
                </template>
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
              :disabled="selectedForInsert.length === 0"
              @click="insertImage"
            >
              {{ selectedForInsert.length > 1 ? `插入 ${selectedForInsert.length} 張圖片` : '插入圖片' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>
