<script setup>
import { Link, router, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { ref } from 'vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
  course: {
    type: Object,
    required: true,
  },
  images: {
    type: Array,
    required: true,
  },
})

const form = useForm({
  image: null,
})

const fileInput = ref(null)
const dragOver = ref(false)
const copiedId = ref(null)

const handleFileChange = (event) => {
  const file = event.target.files[0]
  if (file) {
    uploadFile(file)
  }
}

const handleDrop = (event) => {
  event.preventDefault()
  dragOver.value = false

  const file = event.dataTransfer.files[0]
  if (file) {
    uploadFile(file)
  }
}

const uploadFile = (file) => {
  form.image = file
  form.post(`/admin/courses/${props.course.id}/images`, {
    preserveScroll: true,
    onSuccess: () => {
      form.reset()
      if (fileInput.value) {
        fileInput.value.value = ''
      }
    },
  })
}

const copyUrl = async (image) => {
  try {
    await navigator.clipboard.writeText(image.url)
    copiedId.value = image.id
    setTimeout(() => {
      copiedId.value = null
    }, 2000)
  } catch (err) {
    console.error('Failed to copy URL:', err)
  }
}

const deleteImage = (image) => {
  if (!confirm(`確定要刪除圖片「${image.filename}」嗎？`)) return

  router.delete(`/admin/images/${image.id}`, {
    preserveScroll: true,
  })
}
</script>

<template>
  <div class="px-4 sm:px-6 lg:px-8">
      <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
          <nav class="flex" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-4">
              <li>
                <Link href="/admin/courses" class="text-sm font-medium text-gray-500 hover:text-gray-700">
                  課程管理
                </Link>
              </li>
              <li>
                <div class="flex items-center">
                  <svg class="flex-shrink-0 h-5 w-5 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                  </svg>
                  <span class="ml-4 text-sm font-medium text-gray-500">{{ course.name }}</span>
                </div>
              </li>
            </ol>
          </nav>
          <h1 class="mt-2 text-2xl font-semibold text-gray-900">相簿管理</h1>
          <p class="mt-1 text-sm text-gray-500">
            上傳圖片後，點擊圖片複製 URL 以用於課程介紹 HTML
          </p>
        </div>
        <div class="mt-4 sm:mt-0 flex space-x-3">
          <Link
            :href="`/admin/courses/${course.id}/edit`"
            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
          >
            課程設定
          </Link>
          <Link
            :href="`/admin/courses/${course.id}/chapters`"
            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
          >
            章節編輯
          </Link>
        </div>
      </div>

      <!-- Upload Zone -->
      <div
        class="mb-8 border-2 border-dashed rounded-lg p-8 text-center transition-colors"
        :class="dragOver ? 'border-indigo-500 bg-indigo-50' : 'border-gray-300 hover:border-gray-400'"
        @dragover.prevent="dragOver = true"
        @dragleave="dragOver = false"
        @drop="handleDrop"
      >
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        <div class="mt-4">
          <label class="cursor-pointer">
            <span class="text-indigo-600 hover:text-indigo-500 font-medium">選擇圖片</span>
            <span class="text-gray-500">或拖曳檔案至此處</span>
            <input
              ref="fileInput"
              type="file"
              class="sr-only"
              accept="image/jpeg,image/png,image/gif,image/webp"
              @change="handleFileChange"
            />
          </label>
        </div>
        <p class="mt-2 text-xs text-gray-500">
          支援 JPG, PNG, GIF, WebP，最大 10MB
        </p>
        <div v-if="form.processing" class="mt-4">
          <div class="inline-flex items-center text-sm text-indigo-600">
            <svg class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            上傳中...
          </div>
        </div>
        <p v-if="form.errors.image" class="mt-2 text-sm text-red-600">{{ form.errors.image }}</p>
      </div>

      <!-- Image Grid -->
      <div v-if="images.length > 0" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
        <div
          v-for="image in images"
          :key="image.id"
          class="group relative aspect-square bg-gray-100 rounded-lg overflow-hidden"
        >
          <img
            :src="image.url"
            :alt="image.filename"
            class="w-full h-full object-cover"
          />

          <!-- Overlay -->
          <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-50 transition-opacity flex items-center justify-center opacity-0 group-hover:opacity-100">
            <button
              type="button"
              class="mx-1 p-2 rounded-full bg-white text-gray-700 hover:bg-gray-100"
              title="複製 URL"
              @click="copyUrl(image)"
            >
              <svg v-if="copiedId === image.id" class="w-5 h-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
              <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
              </svg>
            </button>
            <button
              type="button"
              class="mx-1 p-2 rounded-full bg-white text-red-600 hover:bg-red-50"
              title="刪除"
              @click="deleteImage(image)"
            >
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </button>
          </div>

          <!-- Filename -->
          <div class="absolute bottom-0 left-0 right-0 p-2 bg-gradient-to-t from-black/60 to-transparent">
            <p class="text-xs text-white truncate">{{ image.filename }}</p>
          </div>
        </div>
      </div>

      <div v-else class="text-center py-12 bg-gray-50 rounded-lg">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        <p class="mt-4 text-gray-500">尚無圖片，上傳圖片開始建立相簿</p>
      </div>

      <!-- Usage Instructions -->
      <div class="mt-8 bg-blue-50 rounded-lg p-4">
        <h3 class="text-sm font-medium text-blue-900">使用說明</h3>
        <div class="mt-2 text-sm text-blue-700">
          <ol class="list-decimal list-inside space-y-1">
            <li>上傳圖片至相簿</li>
            <li>點擊圖片上的複製按鈕取得 URL</li>
            <li>在課程介紹 HTML 中插入圖片：<code class="bg-blue-100 px-1 rounded">&lt;img src="圖片URL" width="100%" /&gt;</code></li>
          </ol>
        </div>
      </div>
  </div>
</template>
