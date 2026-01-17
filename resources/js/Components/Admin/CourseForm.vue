<script setup>
import { useForm } from '@inertiajs/vue3'
import { ref, computed, watch } from 'vue'

const props = defineProps({
  course: {
    type: Object,
    default: null,
  },
  submitUrl: {
    type: String,
    required: true,
  },
  method: {
    type: String,
    default: 'post',
  },
})

const emit = defineEmits(['submitted'])

const form = useForm({
  name: props.course?.name || '',
  tagline: props.course?.tagline || '',
  description: props.course?.description || '',
  description_html: props.course?.description_html || '',
  price: props.course?.price || '',
  thumbnail: null,
  instructor_name: props.course?.instructor_name || '',
  type: props.course?.type || 'lecture',
  duration_minutes: props.course?.duration_minutes || '',
  sale_at: props.course?.sale_at || '',
  portaly_url: props.course?.portaly_url || '',
  portaly_product_id: props.course?.portaly_product_id || '',
})

const thumbnailPreview = ref(props.course?.thumbnail ? `/storage/${props.course.thumbnail}` : null)

const courseTypes = [
  { value: 'lecture', label: '講座課程' },
  { value: 'mini', label: '迷你課程' },
  { value: 'full', label: '完整課程' },
]

const handleThumbnailChange = (event) => {
  const file = event.target.files[0]
  if (file) {
    form.thumbnail = file
    thumbnailPreview.value = URL.createObjectURL(file)
  }
}

const submit = () => {
  if (props.method === 'put') {
    form.post(props.submitUrl, {
      _method: 'put',
      forceFormData: true,
      preserveScroll: true,
    })
  } else {
    form.post(props.submitUrl, {
      forceFormData: true,
      preserveScroll: true,
    })
  }
}
</script>

<template>
  <form @submit.prevent="submit" class="space-y-6">
    <!-- Basic Info -->
    <div class="bg-white shadow px-4 py-5 sm:rounded-lg sm:p-6">
      <div class="md:grid md:grid-cols-3 md:gap-6">
        <div class="md:col-span-1">
          <h3 class="text-lg font-medium leading-6 text-gray-900">基本資訊</h3>
          <p class="mt-1 text-sm text-gray-500">課程的基本資料。</p>
        </div>
        <div class="mt-5 md:mt-0 md:col-span-2 space-y-6">
          <!-- Name -->
          <div>
            <label for="name" class="block text-sm font-medium text-gray-700">課程名稱</label>
            <input
              id="name"
              v-model="form.name"
              type="text"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              :class="{ 'border-red-300': form.errors.name }"
            />
            <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
          </div>

          <!-- Tagline -->
          <div>
            <label for="tagline" class="block text-sm font-medium text-gray-700">副標題</label>
            <input
              id="tagline"
              v-model="form.tagline"
              type="text"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              :class="{ 'border-red-300': form.errors.tagline }"
            />
            <p v-if="form.errors.tagline" class="mt-1 text-sm text-red-600">{{ form.errors.tagline }}</p>
          </div>

          <!-- Description -->
          <div>
            <label for="description" class="block text-sm font-medium text-gray-700">課程描述</label>
            <textarea
              id="description"
              v-model="form.description"
              rows="3"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              :class="{ 'border-red-300': form.errors.description }"
            />
            <p v-if="form.errors.description" class="mt-1 text-sm text-red-600">{{ form.errors.description }}</p>
          </div>

          <!-- Instructor Name -->
          <div>
            <label for="instructor_name" class="block text-sm font-medium text-gray-700">講師名稱</label>
            <input
              id="instructor_name"
              v-model="form.instructor_name"
              type="text"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              :class="{ 'border-red-300': form.errors.instructor_name }"
            />
            <p v-if="form.errors.instructor_name" class="mt-1 text-sm text-red-600">{{ form.errors.instructor_name }}</p>
          </div>

          <!-- Type -->
          <div>
            <label for="type" class="block text-sm font-medium text-gray-700">課程類型</label>
            <select
              id="type"
              v-model="form.type"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            >
              <option v-for="type in courseTypes" :key="type.value" :value="type.value">
                {{ type.label }}
              </option>
            </select>
            <p v-if="form.errors.type" class="mt-1 text-sm text-red-600">{{ form.errors.type }}</p>
          </div>

          <!-- Thumbnail -->
          <div>
            <label class="block text-sm font-medium text-gray-700">課程縮圖</label>
            <div class="mt-1 flex items-center space-x-4">
              <div class="flex-shrink-0 h-24 w-24 overflow-hidden rounded-lg bg-gray-100">
                <img
                  v-if="thumbnailPreview"
                  :src="thumbnailPreview"
                  class="h-full w-full object-cover"
                />
                <div v-else class="h-full w-full flex items-center justify-center">
                  <svg class="h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                </div>
              </div>
              <label class="cursor-pointer bg-white py-2 px-3 border border-gray-300 rounded-md shadow-sm text-sm leading-4 font-medium text-gray-700 hover:bg-gray-50 focus:outline-none">
                選擇圖片
                <input type="file" class="sr-only" accept="image/*" @change="handleThumbnailChange" />
              </label>
            </div>
            <p v-if="form.errors.thumbnail" class="mt-1 text-sm text-red-600">{{ form.errors.thumbnail }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Pricing & Duration -->
    <div class="bg-white shadow px-4 py-5 sm:rounded-lg sm:p-6">
      <div class="md:grid md:grid-cols-3 md:gap-6">
        <div class="md:col-span-1">
          <h3 class="text-lg font-medium leading-6 text-gray-900">價格與時長</h3>
          <p class="mt-1 text-sm text-gray-500">設定課程價格和預計時間。</p>
        </div>
        <div class="mt-5 md:mt-0 md:col-span-2 space-y-6">
          <!-- Price -->
          <div>
            <label for="price" class="block text-sm font-medium text-gray-700">價格 (TWD)</label>
            <div class="mt-1 relative rounded-md shadow-sm">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <span class="text-gray-500 sm:text-sm">$</span>
              </div>
              <input
                id="price"
                v-model="form.price"
                type="number"
                step="1"
                min="0"
                class="pl-7 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                :class="{ 'border-red-300': form.errors.price }"
              />
            </div>
            <p v-if="form.errors.price" class="mt-1 text-sm text-red-600">{{ form.errors.price }}</p>
          </div>

          <!-- Duration -->
          <div>
            <label for="duration_minutes" class="block text-sm font-medium text-gray-700">時間總長（分鐘）</label>
            <input
              id="duration_minutes"
              v-model="form.duration_minutes"
              type="number"
              min="0"
              placeholder="例如: 190 (顯示為 3小時10分鐘)"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              :class="{ 'border-red-300': form.errors.duration_minutes }"
            />
            <p v-if="form.errors.duration_minutes" class="mt-1 text-sm text-red-600">{{ form.errors.duration_minutes }}</p>
          </div>

          <!-- Sale At -->
          <div>
            <label for="sale_at" class="block text-sm font-medium text-gray-700">預購開賣時間（選填）</label>
            <input
              id="sale_at"
              v-model="form.sale_at"
              type="datetime-local"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              :class="{ 'border-red-300': form.errors.sale_at }"
            />
            <p class="mt-1 text-sm text-gray-500">設定後，發佈時將自動設為「預購中」，時間到會自動切換為「熱賣中」。</p>
            <p v-if="form.errors.sale_at" class="mt-1 text-sm text-red-600">{{ form.errors.sale_at }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Description HTML -->
    <div class="bg-white shadow px-4 py-5 sm:rounded-lg sm:p-6">
      <div class="md:grid md:grid-cols-3 md:gap-6">
        <div class="md:col-span-1">
          <h3 class="text-lg font-medium leading-6 text-gray-900">課程介紹 HTML</h3>
          <p class="mt-1 text-sm text-gray-500">
            自定義 HTML 內容，將顯示於課程販售頁面。
            <span v-if="course" class="block mt-2">
              可到<a :href="`/admin/courses/${course.id}/images`" class="text-indigo-600 hover:text-indigo-500">相簿管理</a>上傳圖片並取得連結。
            </span>
          </p>
        </div>
        <div class="mt-5 md:mt-0 md:col-span-2">
          <textarea
            v-model="form.description_html"
            rows="10"
            placeholder="<h2>課程特色</h2>&#10;<p>這是一門精心設計的課程...</p>"
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm font-mono"
            :class="{ 'border-red-300': form.errors.description_html }"
          />
          <p v-if="form.errors.description_html" class="mt-1 text-sm text-red-600">{{ form.errors.description_html }}</p>
        </div>
      </div>
    </div>

    <!-- Portaly Integration -->
    <div class="bg-white shadow px-4 py-5 sm:rounded-lg sm:p-6">
      <div class="md:grid md:grid-cols-3 md:gap-6">
        <div class="md:col-span-1">
          <h3 class="text-lg font-medium leading-6 text-gray-900">Portaly 整合</h3>
          <p class="mt-1 text-sm text-gray-500">連結 Portaly 商品頁面。</p>
        </div>
        <div class="mt-5 md:mt-0 md:col-span-2 space-y-6">
          <!-- Portaly URL -->
          <div>
            <label for="portaly_url" class="block text-sm font-medium text-gray-700">Portaly 連結</label>
            <input
              id="portaly_url"
              v-model="form.portaly_url"
              type="url"
              placeholder="https://portaly.cc/..."
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              :class="{ 'border-red-300': form.errors.portaly_url }"
            />
            <p v-if="form.errors.portaly_url" class="mt-1 text-sm text-red-600">{{ form.errors.portaly_url }}</p>
          </div>

          <!-- Portaly Product ID -->
          <div>
            <label for="portaly_product_id" class="block text-sm font-medium text-gray-700">Portaly 商品 ID</label>
            <input
              id="portaly_product_id"
              v-model="form.portaly_product_id"
              type="text"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              :class="{ 'border-red-300': form.errors.portaly_product_id }"
            />
            <p v-if="form.errors.portaly_product_id" class="mt-1 text-sm text-red-600">{{ form.errors.portaly_product_id }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Submit -->
    <div class="flex justify-end space-x-3">
      <a
        href="/admin/courses"
        class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
      >
        取消
      </a>
      <button
        type="submit"
        :disabled="form.processing"
        class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
      >
        {{ form.processing ? '儲存中...' : '儲存' }}
      </button>
    </div>
  </form>
</template>
